<?php

namespace App\Services;

use App\Models\BlankForm;
use App\Support\AnswerScanResolver;
use App\Models\Test;
use App\Support\BlankScanLayout;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->map(fn (UploadedFile $file) => $this->scanUploadedForm($test, $file))
            ->all();
    }

    protected function scanUploadedForm(Test $test, UploadedFile $file): array
    {
        $image = $this->loadImage($file);

        try {
            $markers = $this->detectMarkers($image);
            $bitString = $this->decodeBitString($image, $markers);
            $blankFormId = BlankScanLayout::decodeBitString($bitString);

            if (!$blankFormId) {
                throw ValidationException::withMessages([
                    'scan' => 'Не удалось прочитать код бланка. Проверьте, что загружен первый лист бланка ответов.',
                ]);
            }

            $blankForm = BlankForm::with(['test.questions.answers', 'studentGroup', 'groupStudent'])
                ->findOrFail($blankFormId);

            if ((int) $blankForm->test_id !== (int) $test->id) {
                throw ValidationException::withMessages([
                    'scan' => "Скан относится к другому тесту: {$blankForm->form_number}.",
                ]);
            }

            $recognized = $this->extractAnswers($image, $markers, $blankForm);
            $scanPath = Storage::disk('local')->putFile('scans', $file);

            $blankForm = $this->blankFormService->replaceStudentAnswersFromScan(
                $blankForm,
                $recognized['question_answers'],
                [
                    'file_name' => $file->getClientOriginalName(),
                    'scan_path' => $scanPath,
                    'warnings' => $recognized['warnings'],
                    'recognized_answers' => $recognized['display_answers'],
                ]
            );

            $blankForm = $this->gradingService->checkBlankForm($blankForm);
            $grade = $this->gradingService->getStudentGrade($blankForm->fresh('test.questions'));

            return [
                'file_name' => $file->getClientOriginalName(),
                'blank_form_id' => $blankForm->id,
                'form_number' => $blankForm->form_number,
                'student_name' => $blankForm->student_full_name,
                'group_name' => $blankForm->group_name,
                'recognized_answers' => $recognized['display_answers'],
                'warnings' => $recognized['warnings'],
                'score' => $grade['score'],
                'max_score' => $grade['max_score'],
                'grade' => $grade['grade'],
                'status' => $blankForm->status,
            ];
        } finally {
            \imagedestroy($image);
        }
    }

    protected function loadImage(UploadedFile $file)
    {
        $mimeType = $file->getMimeType();
        $path = $file->getRealPath();

        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng')) {
            throw ValidationException::withMessages([
                'scan' => 'На сервере не включено расширение GD для обработки изображений.',
            ]);
        }

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
                'scan' => 'Служебные точки бланка почти не видны на изображении. Сделайте фото ближе, без пересвета и полностью захватите нижнюю часть первого листа.',
            ]);
        }

        $threshold = $minDarkness + ($contrast * 0.45);

        return collect($darknessValues)
            ->map(fn ($darkness) => $darkness >= $threshold ? '1' : '0')
            ->implode('');
    }

    protected function extractAnswers($image, array $markers, BlankForm $blankForm): array
    {
        $questions = $blankForm->test->questions->sortBy('order')->values();
        $questionCount = $questions->count();

        if ($questionCount > BlankScanLayout::maxQuestions()) {
            throw ValidationException::withMessages([
                'scan' => 'Автоматическое сканирование поддерживает не более ' . BlankScanLayout::maxQuestions() . ' вопросов в одном тесте.',
            ]);
        }

        $questionAnswers = [];
        $displayAnswers = [];
        $warnings = [];
        $letters = BlankScanLayout::answerLetters();

        foreach ($questions as $index => $question) {
            $cellMeasurements = [];

            for ($optionIndex = 0; $optionIndex < count($letters); $optionIndex++) {
                if ($optionIndex >= $question->answers->count()) {
                    continue;
                }

                $cell = BlankScanLayout::answerCellMm($questionCount, $index, $optionIndex);

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

            if ($question->type === 'single' && $resolved['ambiguous']) {
                $warnings[] = 'В вопросе ' . ($index + 1) . ' найдено несколько отметок для одиночного выбора.';
            }

            $selectedAnswerIds = collect($selectedIndexes)
                ->map(fn ($optionIndex) => $question->answers[$optionIndex]->id ?? null)
                ->filter()
                ->values()
                ->all();

            $questionAnswers[$question->id] = $selectedAnswerIds;
            $displayAnswers[] = [
                'question_number' => $index + 1,
                'selected' => array_map(fn ($optionIndex) => $letters[$optionIndex], $selectedIndexes),
                'type' => $question->type,
            ];
        }

        return [
            'question_answers' => $questionAnswers,
            'display_answers' => $displayAnswers,
            'warnings' => $warnings,
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
}
