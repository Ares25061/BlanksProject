<?php

namespace App\Services;

use App\Models\BlankForm;
use App\Support\AnswerScanResolver;
use App\Models\Test;
use App\Support\BlankScanLayout;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BlankScanService
{
    public function __construct(
        private BlankFormService $blankFormService,
        private GradingService $gradingService,
    ) {
    }

    public function scanUploadedForms(Test $test, array $files): array
    {
        return collect($files)
            ->map(fn (UploadedFile $file) => $this->scanUploadedPage($test, $file))
            ->groupBy('blank_form_id')
            ->map(fn ($pageScans) => $this->finalizeGroupedScan($pageScans))
            ->values()
            ->all();
    }

    protected function scanUploadedPage(Test $test, UploadedFile $file): array
    {
        $image = $this->loadImage($file);

        try {
            $markers = $this->detectMarkers($image);
            $bitString = $this->decodeBitString($image, $markers);
            $pagePayload = BlankScanLayout::decodePageBitString($bitString);

            if (!$pagePayload) {
                throw ValidationException::withMessages([
                    'scan' => 'Не удалось прочитать код бланка. Проверьте, что загружен корректный лист бланка ответов целиком.',
                ]);
            }

            $blankForm = BlankForm::with(['test.questions.answers', 'studentGroup', 'groupStudent'])
                ->findOrFail($pagePayload['blank_form_id']);

            if ((int) $blankForm->test_id !== (int) $test->id) {
                throw ValidationException::withMessages([
                    'scan' => "Скан относится к другому тесту: {$blankForm->form_number}.",
                ]);
            }

            $expectedPageCount = BlankScanLayout::questionPageCount($blankForm->test->questions->count());
            $pageNumber = min($pagePayload['page_number'], $expectedPageCount);
            $recognized = $this->extractAnswers($image, $markers, $blankForm, $pageNumber);
            $scanPath = $this->storeNormalizedScanImage($image);
            $warnings = $recognized['warnings'];

            if ((int) $pagePayload['page_count'] !== $expectedPageCount) {
                $warnings[] = 'Количество листов на распечатанном бланке отличается от текущей версии теста. Использую актуальную разбивку по листам.';
            }

            return [
                'file_name' => $file->getClientOriginalName(),
                'blank_form_id' => $blankForm->id,
                'blank_form' => $blankForm,
                'form_number' => $blankForm->form_number,
                'student_name' => $blankForm->student_full_name,
                'group_name' => $blankForm->group_name,
                'page_number' => $pageNumber,
                'page_count' => $expectedPageCount,
                'recognized_answers' => $recognized['display_answers'],
                'question_answers' => $recognized['question_answers'],
                'warnings' => $warnings,
                'scan_path' => $scanPath,
                'question_range' => $recognized['question_range'],
            ];
        } finally {
            \imagedestroy($image);
        }
    }

    protected function finalizeGroupedScan($pageScans): array
    {
        $firstPage = $pageScans->first();
        $blankForm = $firstPage['blank_form'];
        $expectedPageCount = (int) $firstPage['page_count'];
        $maxScore = (int) $blankForm->test->questions->sum('points');
        $pagesByNumber = [];
        $warnings = [];

        foreach ($pageScans as $pageScan) {
            $pageNumber = (int) $pageScan['page_number'];

            if (isset($pagesByNumber[$pageNumber])) {
                $warnings[] = 'Лист ответов ' . $pageNumber . ' загружен несколько раз. Использую последний загруженный вариант.';
            }

            $pagesByNumber[$pageNumber] = $pageScan;
        }

        ksort($pagesByNumber);

        $receivedPages = array_keys($pagesByNumber);
        $missingPages = array_values(array_diff(range(1, $expectedPageCount), $receivedPages));

        foreach ($pagesByNumber as $pageScan) {
            $warnings = array_merge($warnings, $pageScan['warnings'] ?? []);
        }

        if ($missingPages !== []) {
            $warnings[] = 'Для формы ' . $blankForm->form_number . ' загружено ' . count($receivedPages) . ' из ' . $expectedPageCount . ' листов ответов. Не хватает: ' . implode(', ', $missingPages) . '.';

            return [
                'file_name' => $this->summarizeFileNames($pagesByNumber),
                'blank_form_id' => $blankForm->id,
                'form_number' => $blankForm->form_number,
                'student_name' => $blankForm->student_full_name,
                'group_name' => $blankForm->group_name,
                'recognized_answers' => collect($pagesByNumber)
                    ->flatMap(fn ($pageScan) => $pageScan['recognized_answers'] ?? [])
                    ->sortBy('question_number')
                    ->values()
                    ->all(),
                'warnings' => array_values(array_unique($warnings)),
                'score' => null,
                'max_score' => $maxScore,
                'grade' => null,
                'status' => 'incomplete_scan',
                'pages_processed' => $receivedPages,
                'expected_pages' => $expectedPageCount,
            ];
        }

        $mergedAnswers = [];
        $displayAnswers = [];
        $pageMetadata = [];

        foreach ($pagesByNumber as $pageNumber => $pageScan) {
            foreach ($pageScan['question_answers'] as $questionId => $answerIds) {
                $mergedAnswers[$questionId] = $answerIds;
            }

            $displayAnswers = array_merge($displayAnswers, $pageScan['recognized_answers'] ?? []);
            $pageMetadata[] = [
                'page_number' => $pageNumber,
                'file_name' => $pageScan['file_name'] ?? null,
                'scan_path' => $pageScan['scan_path'] ?? null,
                'question_range' => $pageScan['question_range'] ?? null,
            ];
        }

        usort($displayAnswers, fn (array $left, array $right) => ($left['question_number'] ?? 0) <=> ($right['question_number'] ?? 0));

        $blankForm = $this->blankFormService->replaceStudentAnswersFromScan(
            $blankForm,
            $mergedAnswers,
            [
                'file_name' => $this->summarizeFileNames($pagesByNumber),
                'files' => array_values(array_map(fn (array $pageScan) => $pageScan['file_name'] ?? '', $pagesByNumber)),
                'scan_path' => $pagesByNumber[1]['scan_path'] ?? ($firstPage['scan_path'] ?? null),
                'warnings' => array_values(array_unique($warnings)),
                'recognized_answers' => $displayAnswers,
                'pages' => $pageMetadata,
            ]
        );

        $blankForm = $this->gradingService->checkBlankForm($blankForm);
        $grade = $this->gradingService->getStudentGrade($blankForm->fresh('test.questions'));

        return [
            'file_name' => $this->summarizeFileNames($pagesByNumber),
            'blank_form_id' => $blankForm->id,
            'form_number' => $blankForm->form_number,
            'student_name' => $blankForm->student_full_name,
            'group_name' => $blankForm->group_name,
            'recognized_answers' => $displayAnswers,
            'warnings' => array_values(array_unique($warnings)),
            'score' => $grade['score'],
            'max_score' => $grade['max_score'],
            'grade' => $grade['grade'],
            'status' => $blankForm->status,
            'pages_processed' => $receivedPages,
            'expected_pages' => $expectedPageCount,
        ];
    }

    protected function summarizeFileNames(array $pagesByNumber): string
    {
        $fileNames = collect($pagesByNumber)
            ->map(fn (array $pageScan) => trim((string) ($pageScan['file_name'] ?? '')))
            ->filter()
            ->values();

        if ($fileNames->isEmpty()) {
            return '';
        }

        if ($fileNames->count() === 1) {
            return (string) $fileNames->first();
        }

        return $fileNames->implode(', ');
    }

    protected function loadImage(UploadedFile $file)
    {
        $mimeType = $file->getMimeType();
        $path = $file->getRealPath();

        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng') || !function_exists('imagejpeg') || !function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages([
                'scan' => 'На сервере не включено расширение GD для обработки изображений.',
            ]);
        }

        if ($this->isPdfFile($file, $mimeType)) {
            $image = $this->loadPdfFirstPage($path);
        } else {
            if ($mimeType === 'image/webp' && !function_exists('imagecreatefromwebp')) {
                throw ValidationException::withMessages([
                    'scan' => 'На сервере не включена поддержка WEBP в расширении GD.',
                ]);
            }

            $image = match ($mimeType) {
                'image/png' => \imagecreatefrompng($path),
                'image/webp' => \imagecreatefromwebp($path),
                default => \imagecreatefromjpeg($path),
            };
        }

        if (!$image) {
            throw ValidationException::withMessages([
                'scan' => 'Не удалось открыть изображение скана.',
            ]);
        }

        if (\imagesx($image) > \imagesy($image)) {
            $rotated = \imagerotate($image, 90, 255);
            \imagedestroy($image);
            $image = $rotated;
        }

        return $image;
    }

    protected function detectMarkers($image): array
    {
        $width = \imagesx($image);
        $height = \imagesy($image);
        $searchWidth = (int) floor($width * 0.22);
        $searchHeight = (int) floor($height * 0.18);
        $window = max(18, (int) floor(min($width, $height) * 0.035));

        return [
            'tl' => $this->detectMarkerInRegion($image, 0, $searchWidth, 0, $searchHeight, $window),
            'tr' => $this->detectMarkerInRegion($image, $width - $searchWidth, $width, 0, $searchHeight, $window),
            'bl' => $this->detectMarkerInRegion($image, 0, $searchWidth, $height - $searchHeight, $height, $window),
            'br' => $this->detectMarkerInRegion($image, $width - $searchWidth, $width, $height - $searchHeight, $height, $window),
        ];
    }

    protected function detectMarkerInRegion($image, int $xStart, int $xEnd, int $yStart, int $yEnd, int $window): array
    {
        $step = max(4, (int) floor($window / 4));
        $bestScore = -1.0;
        $bestPoint = ['x' => $xStart + $window / 2, 'y' => $yStart + $window / 2];

        for ($y = $yStart; $y <= $yEnd - $window; $y += $step) {
            for ($x = $xStart; $x <= $xEnd - $window; $x += $step) {
                $score = $this->averageDarkness($image, $x, $y, $window, $window);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestPoint = [
                        'x' => $x + ($window / 2),
                        'y' => $y + ($window / 2),
                    ];
                }
            }
        }

        return $this->refineMarkerCenter($image, $bestPoint, $window);
    }

    protected function decodeBitString($image, array $markers): string
    {
        $darknessValues = [];

        for ($index = 0; $index < BlankScanLayout::CODE_BITS; $index++) {
            $cell = BlankScanLayout::codeCellMm($index);

            $darknessValues[] = $this->sampleDarknessMm(
                $image,
                $markers,
                $cell['left'] + 0.35,
                $cell['top'] + 0.35,
                $cell['width'] - 0.7,
                $cell['height'] - 0.7,
            );
        }

        $minDarkness = min($darknessValues);
        $maxDarkness = max($darknessValues);
        $contrast = $maxDarkness - $minDarkness;

        if ($contrast < 0.06) {
            throw ValidationException::withMessages([
                'scan' => 'Служебные точки бланка почти не видны на изображении. Сделайте фото ближе, без пересвета и полностью захватите нижнюю часть листа.',
            ]);
        }

        $threshold = $minDarkness + ($contrast * 0.45);

        return collect($darknessValues)
            ->map(fn ($darkness) => $darkness >= $threshold ? '1' : '0')
            ->implode('');
    }

    protected function extractAnswers($image, array $markers, BlankForm $blankForm, int $pageNumber): array
    {
        $questions = $blankForm->test->questions->sortBy('order')->values();
        $startIndex = BlankScanLayout::questionStartIndexForPage($pageNumber);
        $pageQuestions = $questions
            ->slice($startIndex, BlankScanLayout::questionsPerPage())
            ->values();

        $questionAnswers = [];
        $displayAnswers = [];
        $warnings = [];
        $letters = BlankScanLayout::answerLetters();

        foreach ($pageQuestions as $index => $question) {
            $cellMeasurements = [];

            for ($optionIndex = 0; $optionIndex < count($letters); $optionIndex++) {
                if ($optionIndex >= $question->answers->count()) {
                    continue;
                }

                $cell = BlankScanLayout::answerCellMm($pageQuestions->count(), $index, $optionIndex);

                $darkRatio = $this->sampleDarkRatioMm(
                    $image,
                    $markers,
                    $cell['left'] + 0.8,
                    $cell['top'] + 0.8,
                    $cell['size'] - 1.6,
                    $cell['size'] - 1.6,
                );

                $darkness = $this->sampleDarknessMm(
                    $image,
                    $markers,
                    $cell['left'] + 0.8,
                    $cell['top'] + 0.8,
                    $cell['size'] - 1.6,
                    $cell['size'] - 1.6,
                );

                $cellMeasurements[] = [
                    'option_index' => $optionIndex,
                    'dark_ratio' => $darkRatio,
                    'darkness' => $darkness,
                    'score' => AnswerScanResolver::buildMarkScore([
                        'dark_ratio' => $darkRatio,
                        'darkness' => $darkness,
                    ]),
                ];
            }

            $resolved = AnswerScanResolver::resolve($question->type, $cellMeasurements);
            $selectedIndexes = $resolved['selected_indexes'];
            $questionNumber = $startIndex + $index + 1;

            if ($question->type === 'single' && $resolved['ambiguous']) {
                $warnings[] = 'В вопросе ' . $questionNumber . ' найдено несколько отметок для одиночного выбора.';
            }

            $selectedAnswerIds = collect($selectedIndexes)
                ->map(fn ($optionIndex) => $question->answers[$optionIndex]->id ?? null)
                ->filter()
                ->values()
                ->all();

            $questionAnswers[$question->id] = $selectedAnswerIds;
            $displayAnswers[] = [
                'question_number' => $questionNumber,
                'selected' => array_map(fn ($optionIndex) => $letters[$optionIndex], $selectedIndexes),
                'type' => $question->type,
                'page_number' => $pageNumber,
            ];
        }

        return [
            'question_answers' => $questionAnswers,
            'display_answers' => $displayAnswers,
            'warnings' => $warnings,
            'question_range' => [
                'start' => $startIndex + 1,
                'end' => $startIndex + $pageQuestions->count(),
            ],
        ];
    }

    protected function sampleDarknessMm($image, array $markers, float $xMm, float $yMm, float $widthMm, float $heightMm): float
    {
        [$x1, $y1] = $this->projectMmToPixel($markers, $xMm, $yMm);
        [$x2, $y2] = $this->projectMmToPixel($markers, $xMm + $widthMm, $yMm + $heightMm);

        return $this->averageDarkness(
            $image,
            (int) floor(min($x1, $x2)),
            (int) floor(min($y1, $y2)),
            (int) max(1, abs($x2 - $x1)),
            (int) max(1, abs($y2 - $y1)),
        );
    }

    protected function sampleDarkRatioMm($image, array $markers, float $xMm, float $yMm, float $widthMm, float $heightMm): float
    {
        [$x1, $y1] = $this->projectMmToPixel($markers, $xMm, $yMm);
        [$x2, $y2] = $this->projectMmToPixel($markers, $xMm + $widthMm, $yMm + $heightMm);

        return $this->darkPixelRatio(
            $image,
            (int) floor(min($x1, $x2)),
            (int) floor(min($y1, $y2)),
            (int) max(1, abs($x2 - $x1)),
            (int) max(1, abs($y2 - $y1)),
        );
    }

    protected function averageDarkness($image, int $x, int $y, int $width, int $height): float
    {
        $width = max(1, $width);
        $height = max(1, $height);
        $total = 0.0;
        $count = 0;
        $stepX = max(1, (int) floor($width / 12));
        $stepY = max(1, (int) floor($height / 12));

        for ($yy = $y; $yy < $y + $height; $yy += $stepY) {
            for ($xx = $x; $xx < $x + $width; $xx += $stepX) {
                $count++;
                $total += $this->pixelDarkness($image, $xx, $yy);
            }
        }

        return $count > 0 ? $total / $count : 0.0;
    }

    protected function darkPixelRatio($image, int $x, int $y, int $width, int $height): float
    {
        $width = max(1, $width);
        $height = max(1, $height);
        $darkPixels = 0;
        $count = 0;
        $stepX = max(1, (int) floor($width / 16));
        $stepY = max(1, (int) floor($height / 16));

        for ($yy = $y; $yy < $y + $height; $yy += $stepY) {
            for ($xx = $x; $xx < $x + $width; $xx += $stepX) {
                $count++;

                if ($this->pixelDarkness($image, $xx, $yy) > 0.32) {
                    $darkPixels++;
                }
            }
        }

        return $count > 0 ? $darkPixels / $count : 0.0;
    }

    protected function pixelDarkness($image, int $x, int $y): float
    {
        $x = max(0, min(\imagesx($image) - 1, $x));
        $y = max(0, min(\imagesy($image) - 1, $y));

        $rgb = \imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $gray = ($r * 0.299) + ($g * 0.587) + ($b * 0.114);

        return (255 - $gray) / 255;
    }

    protected function refineMarkerCenter($image, array $point, int $window): array
    {
        $radius = (int) ceil($window * 0.9);
        $minX = max(0, (int) floor($point['x'] - $radius));
        $maxX = min(\imagesx($image) - 1, (int) ceil($point['x'] + $radius));
        $minY = max(0, (int) floor($point['y'] - $radius));
        $maxY = min(\imagesy($image) - 1, (int) ceil($point['y'] + $radius));

        $darkMinX = null;
        $darkMaxX = null;
        $darkMinY = null;
        $darkMaxY = null;
        $darkPixels = 0;

        for ($y = $minY; $y <= $maxY; $y++) {
            for ($x = $minX; $x <= $maxX; $x++) {
                if ($this->pixelDarkness($image, $x, $y) < 0.55) {
                    continue;
                }

                $darkPixels++;
                $darkMinX = $darkMinX === null ? $x : min($darkMinX, $x);
                $darkMaxX = $darkMaxX === null ? $x : max($darkMaxX, $x);
                $darkMinY = $darkMinY === null ? $y : min($darkMinY, $y);
                $darkMaxY = $darkMaxY === null ? $y : max($darkMaxY, $y);
            }
        }

        if ($darkPixels < 12 || $darkMinX === null || $darkMinY === null || $darkMaxX === null || $darkMaxY === null) {
            return $point;
        }

        return [
            'x' => ($darkMinX + $darkMaxX) / 2,
            'y' => ($darkMinY + $darkMaxY) / 2,
        ];
    }

    protected function projectMmToPixel(array $markers, float $xMm, float $yMm): array
    {
        $centers = BlankScanLayout::markerCentersMm();
        $u = ($xMm - $centers['tl']['x']) / ($centers['tr']['x'] - $centers['tl']['x']);
        $v = ($yMm - $centers['tl']['y']) / ($centers['bl']['y'] - $centers['tl']['y']);

        $x = ((1 - $u) * (1 - $v) * $markers['tl']['x'])
            + ($u * (1 - $v) * $markers['tr']['x'])
            + ((1 - $u) * $v * $markers['bl']['x'])
            + ($u * $v * $markers['br']['x']);

        $y = ((1 - $u) * (1 - $v) * $markers['tl']['y'])
            + ($u * (1 - $v) * $markers['tr']['y'])
            + ((1 - $u) * $v * $markers['bl']['y'])
            + ($u * $v * $markers['br']['y']);

        return [$x, $y];
    }

    protected function isPdfFile(UploadedFile $file, ?string $mimeType): bool
    {
        return ($mimeType === 'application/pdf')
            || Str::lower($file->getClientOriginalExtension() ?: $file->extension() ?: '') === 'pdf';
    }

    protected function loadPdfFirstPage(string $path)
    {
        if (class_exists(\Imagick::class)) {
            try {
                $imagick = new \Imagick();
                $imagick->setResolution(200, 200);
                $imagick->readImage($path . '[0]');
                $imagick->setImageBackgroundColor('white');
                $imagick = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $imagick->setImageFormat('png');
                $blob = $imagick->getImageBlob();
                $imagick->clear();
                $imagick->destroy();

                $image = \imagecreatefromstring($blob);
                if ($image !== false) {
                    return $image;
                }
            } catch (\Throwable) {
                // Fallback to CLI converters below.
            }
        }

        $tempDirectory = $this->ensureTemporaryDirectory('pdf-conversion');
        $tempPrefix = $tempDirectory . DIRECTORY_SEPARATOR . 'blank-pdf-' . Str::uuid();
        $generatedFiles = [];

        try {
            $pdftoppm = $this->findShellCommand('pdftoppm');
            if ($pdftoppm) {
                $outputPath = $tempPrefix . '.png';
                $result = $this->runShellCommand(
                    $this->quoteShellExecutable($pdftoppm) . ' -f 1 -l 1 -singlefile -png ' . escapeshellarg($path) . ' ' . escapeshellarg($tempPrefix)
                );
                $generatedFiles[] = $outputPath;

                if ($result['exit_code'] === 0 && is_file($outputPath)) {
                    $image = \imagecreatefrompng($outputPath);
                    if ($image !== false) {
                        return $image;
                    }
                }
            }

            $magick = $this->findShellCommand('magick');
            if ($magick) {
                $outputPath = $tempPrefix . '-magick.png';
                $result = $this->runShellCommand(
                    $this->quoteShellExecutable($magick) . ' -density 200 ' . escapeshellarg($path . '[0]') . ' -background white -alpha remove -alpha off ' . escapeshellarg($outputPath)
                );
                $generatedFiles[] = $outputPath;

                if ($result['exit_code'] === 0 && is_file($outputPath)) {
                    $image = \imagecreatefrompng($outputPath);
                    if ($image !== false) {
                        return $image;
                    }
                }
            }
        } finally {
            foreach ($generatedFiles as $generatedFile) {
                if (is_file($generatedFile)) {
                    @unlink($generatedFile);
                }
            }
        }

        throw ValidationException::withMessages([
            'scan' => 'PDF загружен, но на сервере нет конвертера листов в изображение. Нужен Imagick, pdftoppm или ImageMagick. Пока загрузите страницы как JPG, PNG или WEBP.',
        ]);
    }

    protected function storeNormalizedScanImage($image): string
    {
        ob_start();
        \imagejpeg($image, null, 92);
        $binary = ob_get_clean();

        if ($binary === false) {
            throw ValidationException::withMessages([
                'scan' => 'Не удалось сохранить обработанный скан.',
            ]);
        }

        $path = 'scans/' . now()->format('YmdHis') . '-' . Str::uuid() . '.jpg';
        Storage::disk('local')->put($path, $binary);

        return $path;
    }

    protected function ensureTemporaryDirectory(string $suffix): string
    {
        $directory = storage_path('app/' . trim($suffix, '/\\'));

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw ValidationException::withMessages([
                'scan' => 'Не удалось подготовить временную папку для обработки PDF.',
            ]);
        }

        return $directory;
    }

    protected function findShellCommand(string $command): ?string
    {
        if (!function_exists('exec')) {
            return null;
        }

        $safeCommand = preg_match('/^[A-Za-z0-9._-]+$/', $command)
            ? $command
            : escapeshellarg($command);

        $probe = PHP_OS_FAMILY === 'Windows'
            ? 'where ' . $safeCommand . ' 2>NUL'
            : 'command -v ' . $safeCommand . ' 2>/dev/null';

        exec($probe, $output, $exitCode);

        if ($exitCode !== 0 || empty($output[0])) {
            return null;
        }

        return trim((string) $output[0]);
    }

    protected function runShellCommand(string $command): array
    {
        if (!function_exists('exec')) {
            return [
                'exit_code' => 1,
                'output' => [],
            ];
        }

        $output = [];
        $exitCode = 1;
        exec($command . (PHP_OS_FAMILY === 'Windows' ? ' 2>NUL' : ' 2>/dev/null'), $output, $exitCode);

        return [
            'exit_code' => $exitCode,
            'output' => $output,
        ];
    }

    protected function quoteShellExecutable(string $command): string
    {
        return preg_match('/\s/', $command) ? escapeshellarg($command) : $command;
    }
}
