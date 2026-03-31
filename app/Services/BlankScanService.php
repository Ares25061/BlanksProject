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
    protected const OCR_NORMALIZED_SCALE = 6.0;
    protected array $normalizedAnswerGridCache = [];

    public function __construct(
        private BlankFormService $blankFormService,
        private GradingService $gradingService,
        private ScanPreviewService $scanPreviewService,
        private TestVariantService $testVariantService,
    ) {
    }

    public function scanUploadedForms(Test $test, array $files, ?int $previewVariantNumber = null): array
    {
        $normalizedPreviewVariantNumber = $this->testVariantService->normalizeVariantNumber(
            $test,
            $previewVariantNumber ?? 1
        );

        return collect($files)
            ->map(fn (UploadedFile $file) => $this->scanUploadedPage($test, $file, $normalizedPreviewVariantNumber))
            ->groupBy('processing_key')
            ->map(fn ($pageScans) => $this->finalizeGroupedScan($pageScans))
            ->values()
            ->all();
    }

    protected function scanUploadedPage(Test $test, UploadedFile $file, int $previewVariantNumber = 1): array
    {
        $image = $this->loadImage($file);

        try {
            $scanFrame = $this->resolveScanFrame($image);
            $image = $scanFrame['image'];
            $markers = $scanFrame['markers'];
            $bitString = $scanFrame['bit_string'];
            $projectionCalibration = $scanFrame['projection_calibration'];
            $pagePayload = $scanFrame['page_payload'];

            $blankForm = BlankForm::with(['test.questions.answers', 'studentGroup', 'groupStudent'])
                ->find($pagePayload['blank_form_id']);
            $isMissingLocalBlankForm = !$blankForm;

            if ($isMissingLocalBlankForm) {
                $blankForm = $this->buildTransientPreviewBlankForm(
                    $test,
                    (int) $pagePayload['blank_form_id'],
                    $previewVariantNumber
                );
            }

            $variantNumber = $this->testVariantService->normalizeVariantNumber(
                $blankForm->test,
                $blankForm->variant_number ?? $previewVariantNumber
            );
            $blankForm->setAttribute('variant_number', $variantNumber);
            $isCurrentTestForm = !$isMissingLocalBlankForm && (int) $blankForm->test_id === (int) $test->id;

            $variantQuestions = $this->testVariantService
                ->questionsForVariant($blankForm->test, $variantNumber)
                ->values();
            $expectedPageCount = BlankScanLayout::questionPageCount($variantQuestions->count());
            $pageNumber = min($pagePayload['page_number'], $expectedPageCount);
            $recognized = $this->extractAnswers($image, $markers, $blankForm, $pageNumber, $projectionCalibration);
            $scanPath = $this->storeNormalizedScanImage($image);
            $warnings = $recognized['warnings'];

            if ($isMissingLocalBlankForm) {
                $warnings[] = 'Код бланка ' . $pagePayload['blank_form_id'] . ' не найден в локальной базе. Выполняю только временный OCR-разбор по текущему тесту без сохранения и выставления оценки.';
            } elseif (!$isCurrentTestForm) {
                $warnings[] = "Скан относится к другому тесту: {$blankForm->form_number}. Сохраняю только временный OCR-разбор без выставления оценки.";
            }

            if ((int) $pagePayload['page_count'] !== $expectedPageCount) {
                $warnings[] = 'Количество листов на распечатанном бланке отличается от текущей версии теста. Использую актуальную разбивку по листам.';
            }

            return [
                'file_name' => $file->getClientOriginalName(),
                'processing_key' => $isCurrentTestForm
                    ? 'blank-form:' . $blankForm->id
                    : 'foreign-preview:' . ($blankForm->id ?: ('remote-' . $pagePayload['blank_form_id'])),
                'processing_mode' => $isCurrentTestForm ? 'persist' : 'foreign_preview',
                'blank_form_id' => $isCurrentTestForm ? $blankForm->id : null,
                'blank_form' => $blankForm,
                'form_number' => $blankForm->form_number,
                'student_name' => $blankForm->student_full_name,
                'group_name' => $blankForm->group_name,
                'variant_number' => $variantNumber,
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

    protected function resolveScanFrame($image): array
    {
        $angles = $this->scanOrientationAngles($image);
        $lastValidationException = null;

        foreach ($angles as $angle) {
            $candidate = $angle === 0
                ? $image
                : $this->rotateImageForScan($image, $angle);

            if (!$candidate) {
                continue;
            }

            try {
                $frame = $this->tryResolveScanFrame($candidate);
            } catch (ValidationException $exception) {
                $lastValidationException = $exception;
                $frame = null;
            }

            if ($frame) {
                if ($candidate !== $image) {
                    \imagedestroy($image);
                }

                $frame['image'] = $candidate;

                return $frame;
            }

            if ($candidate !== $image) {
                \imagedestroy($candidate);
            }
        }

        if ($lastValidationException) {
            throw $lastValidationException;
        }

        throw ValidationException::withMessages([
            'scan' => 'Не удалось прочитать код бланка. Проверьте, что загружен корректный лист бланка ответов целиком.',
        ]);
    }

    protected function tryResolveScanFrame($image): ?array
    {
        $markers = $this->detectMarkers($image);
        $bitString = $this->decodeBitString($image, $markers);
        $pagePayload = BlankScanLayout::decodePageBitString($bitString);

        if (!$pagePayload) {
            return null;
        }

        return [
            'markers' => $markers,
            'bit_string' => $bitString,
            'projection_calibration' => $this->buildProjectionCalibration($image, $markers, $bitString),
            'page_payload' => $pagePayload,
        ];
    }

    protected function scanOrientationAngles($image): array
    {
        if (\imagesx($image) > \imagesy($image)) {
            return [90, 270, 0, 180];
        }

        return [0, 180, 90, 270];
    }

    protected function rotateImageForScan($image, int $angle)
    {
        $rotated = \imagerotate($image, $angle, 0xFFFFFF);

        return $rotated ?: null;
    }

    protected function buildTransientPreviewBlankForm(Test $test, int $remoteBlankFormId, int $previewVariantNumber): BlankForm
    {
        $blankForm = new BlankForm([
            'test_id' => $test->id,
            'form_number' => 'REMOTE-' . $remoteBlankFormId,
            'variant_number' => $this->testVariantService->normalizeVariantNumber($test, $previewVariantNumber),
            'last_name' => 'Чужой',
            'first_name' => 'бланк',
            'group_name' => '',
            'status' => 'foreign_preview',
            'metadata' => [
                'remote_blank_form_id' => $remoteBlankFormId,
                'is_missing_local_blank_form' => true,
            ],
        ]);

        $blankForm->setRelation('test', $test->loadMissing('questions.answers'));
        $blankForm->setRelation('studentGroup', null);
        $blankForm->setRelation('groupStudent', null);

        return $blankForm;
    }

    protected function finalizeGroupedScan($pageScans): array
    {
        $firstPage = $pageScans->first();

        if (($firstPage['processing_mode'] ?? 'persist') === 'foreign_preview') {
            return $this->finalizeForeignPreview($pageScans);
        }

        $blankForm = $firstPage['blank_form'];
        $expectedPageCount = (int) $firstPage['page_count'];
        $maxScore = (int) $this->testVariantService
            ->questionsForVariant($blankForm->test, $blankForm->variant_number ?? 1)
            ->sum('points');
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
                'variant_number' => $blankForm->variant_number ?? 1,
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
            'variant_number' => $blankForm->variant_number ?? 1,
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

    protected function finalizeForeignPreview($pageScans): array
    {
        $firstPage = $pageScans->first();
        $blankForm = $firstPage['blank_form'];
        $expectedPageCount = (int) $firstPage['page_count'];
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
                'blank_form_id' => null,
                'form_number' => $blankForm->form_number,
                'student_name' => $blankForm->student_full_name,
                'group_name' => $blankForm->group_name,
                'variant_number' => $blankForm->variant_number ?? 1,
                'recognized_answers' => collect($pagesByNumber)
                    ->flatMap(fn ($pageScan) => $pageScan['recognized_answers'] ?? [])
                    ->sortBy('question_number')
                    ->values()
                    ->all(),
                'warnings' => array_values(array_unique($warnings)),
                'score' => null,
                'max_score' => (int) $this->testVariantService
                    ->questionsForVariant($blankForm->test, $blankForm->variant_number ?? 1)
                    ->sum('points'),
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

        $preview = $this->scanPreviewService->createPreview((int) auth()->id(), $this->gradingService->buildTransientScanReview(
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
        ));

        return [
            'file_name' => $this->summarizeFileNames($pagesByNumber),
            'blank_form_id' => null,
            'preview_token' => $preview['token'],
            'form_number' => $blankForm->form_number,
            'student_name' => $blankForm->student_full_name,
            'group_name' => $blankForm->group_name,
            'variant_number' => $blankForm->variant_number ?? 1,
            'recognized_answers' => $displayAnswers,
            'warnings' => array_values(array_unique($warnings)),
            'score' => data_get($preview, 'grade.score'),
            'max_score' => data_get($preview, 'grade.max_score'),
            'grade' => data_get($preview, 'grade.grade'),
            'status' => 'foreign_preview',
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

    protected function extractAnswers($image, array $markers, BlankForm $blankForm, int $pageNumber, ?array $projectionCalibration = null): array
    {
        $questions = $this->testVariantService
            ->questionsForVariant($blankForm->test, $blankForm->variant_number ?? 1)
            ->values();
        $startIndex = BlankScanLayout::questionStartIndexForPage($pageNumber);
        $pageQuestions = $questions
            ->slice($startIndex, BlankScanLayout::questionsPerPage())
            ->values();

        $questionAnswers = [];
        $displayAnswers = [];
        $warnings = [];
        $letters = BlankScanLayout::answerLetters();
        $normalizedPage = $this->normalizePageImage($image, $markers, $projectionCalibration, self::OCR_NORMALIZED_SCALE);

        try {
            foreach ($pageQuestions as $index => $question) {
                $cellMeasurements = [];
                $variantAnswers = $this->testVariantService->orderedAnswersForQuestion($question, $blankForm->variant_number ?? 1);

                for ($optionIndex = 0; $optionIndex < count($letters); $optionIndex++) {
                    if ($optionIndex >= $variantAnswers->count()) {
                        continue;
                    }

                    $cellRect = $this->detectNormalizedAnswerCellRect(
                        $normalizedPage,
                        $pageQuestions->count(),
                        $index,
                        $optionIndex,
                        self::OCR_NORMALIZED_SCALE
                    );
                    $scanWindow = $this->detectNormalizedAnswerScanWindow(
                        $normalizedPage,
                        $pageQuestions->count(),
                        $index,
                        $optionIndex,
                        self::OCR_NORMALIZED_SCALE
                    );
                    $coreWindow = $this->centeredRectWithinRect($cellRect, BlankScanLayout::ANSWER_CORE_WINDOW_RATIO);

                    $darkRatio = $this->darkPixelRatio(
                        $normalizedPage,
                        $scanWindow['x'],
                        $scanWindow['y'],
                        $scanWindow['width'],
                        $scanWindow['height'],
                    );

                    $darkness = $this->averageDarkness(
                        $normalizedPage,
                        $scanWindow['x'],
                        $scanWindow['y'],
                        $scanWindow['width'],
                        $scanWindow['height'],
                    );
                    $coreDarkRatio = $this->darkPixelRatioWithThreshold(
                        $normalizedPage,
                        $coreWindow['x'],
                        $coreWindow['y'],
                        $coreWindow['width'],
                        $coreWindow['height'],
                        0.45
                    );
                    $coreStrongRatio = $this->darkPixelRatioWithThreshold(
                        $normalizedPage,
                        $coreWindow['x'],
                        $coreWindow['y'],
                        $coreWindow['width'],
                        $coreWindow['height'],
                        0.60
                    );
                    $inkRatio = $this->inkSignal(
                        $normalizedPage,
                        $scanWindow['x'],
                        $scanWindow['y'],
                        $scanWindow['width'],
                        $scanWindow['height'],
                    );
                    $coreInkRatio = $this->inkSignal(
                        $normalizedPage,
                        $coreWindow['x'],
                        $coreWindow['y'],
                        $coreWindow['width'],
                        $coreWindow['height'],
                    );

                    $cellMeasurements[] = [
                        'option_index' => $optionIndex,
                        'dark_ratio' => $darkRatio,
                        'darkness' => $darkness,
                        'core_dark_ratio' => $coreDarkRatio,
                        'core_strong_ratio' => $coreStrongRatio,
                        'ink_ratio' => $inkRatio,
                        'core_ink_ratio' => $coreInkRatio,
                        'score' => AnswerScanResolver::buildMarkScore([
                            'dark_ratio' => $darkRatio,
                            'darkness' => $darkness,
                            'core_dark_ratio' => $coreDarkRatio,
                            'core_strong_ratio' => $coreStrongRatio,
                            'ink_ratio' => $inkRatio,
                            'core_ink_ratio' => $coreInkRatio,
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
                    ->map(fn ($optionIndex) => $variantAnswers[$optionIndex]->id ?? null)
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
        } finally {
            \imagedestroy($normalizedPage);
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

    protected function detectAnswerScanWindow($image, array $markers, int $questionCount, int $questionIndex, int $optionIndex, ?array $projectionCalibration = null): array
    {
        $window = BlankScanLayout::answerScanWindowMm($questionCount, $questionIndex, $optionIndex);
        return $this->projectSquareMmToPixelQuad(
            $markers,
            $window['left'],
            $window['top'],
            $window['size'],
            $projectionCalibration
        );
    }

    protected function detectNormalizedAnswerScanWindow($normalizedPage, int $questionCount, int $questionIndex, int $optionIndex, float $scale): array
    {
        $cellRect = $this->detectNormalizedAnswerCellRect($normalizedPage, $questionCount, $questionIndex, $optionIndex, $scale);

        return $this->centeredRectWithinRect($cellRect, BlankScanLayout::ANSWER_SCAN_WINDOW_RATIO);
    }

    protected function detectNormalizedAnswerGuideWindow($normalizedPage, int $questionCount, int $questionIndex, int $optionIndex, float $scale): array
    {
        $cellRect = $this->detectNormalizedAnswerCellRect($normalizedPage, $questionCount, $questionIndex, $optionIndex, $scale);

        return $this->centeredRectWithinRect($cellRect, BlankScanLayout::ANSWER_CELL_GUIDE_RATIO);
    }

    protected function detectNormalizedAnswerCellRect($normalizedPage, int $questionCount, int $questionIndex, int $optionIndex, float $scale): array
    {
        $grid = $this->detectNormalizedAnswerGrid($normalizedPage, $questionCount, $scale);
        $row = $grid['rows'][$questionIndex] ?? null;
        $column = $grid['columns'][$optionIndex] ?? null;

        if (!$row || !$column) {
            $cell = BlankScanLayout::answerCellMm($questionCount, $questionIndex, $optionIndex);

            return $this->mmSquareToPixelRect($cell['left'], $cell['top'], $cell['size'], $scale);
        }

        return [
            'x' => (int) round($column['left']),
            'y' => (int) round($row['top']),
            'width' => max(1, (int) round($column['right'] - $column['left'])),
            'height' => max(1, (int) round($row['bottom'] - $row['top'])),
        ];
    }

    protected function mmSquareToPixelRect(float $leftMm, float $topMm, float $sizeMm, float $scale): array
    {
        $left = (int) round($leftMm * $scale);
        $top = (int) round($topMm * $scale);
        $size = max(1, (int) round($sizeMm * $scale));

        return [
            'x' => $left,
            'y' => $top,
            'width' => $size,
            'height' => $size,
        ];
    }

    protected function centeredRectWithinRect(array $rect, float $ratio): array
    {
        $width = max(1, (int) round($rect['width'] * $ratio));
        $height = max(1, (int) round($rect['height'] * $ratio));

        return [
            'x' => (int) round($rect['x'] + (($rect['width'] - $width) / 2)),
            'y' => (int) round($rect['y'] + (($rect['height'] - $height) / 2)),
            'width' => $width,
            'height' => $height,
        ];
    }

    protected function normalizedAnswerFieldArea(int $questionCount, int $rowCount, float $scale): array
    {
        $firstCell = BlankScanLayout::answerCellMm($questionCount, 0, 0);
        $lastCell = BlankScanLayout::answerCellMm(
            $questionCount,
            max(0, $rowCount - 1),
            BlankScanLayout::ANSWER_OPTION_COUNT - 1
        );
        $padding = 1.2 * $scale;

        return [
            'left' => max(0, (int) floor(($firstCell['left'] * $scale) - $padding)),
            'right' => max(1, (int) ceil((($lastCell['left'] + $lastCell['size']) * $scale) + $padding)),
            'top' => max(0, (int) floor(($firstCell['top'] * $scale) - $padding)),
            'bottom' => max(1, (int) ceil((($lastCell['top'] + $lastCell['size']) * $scale) + $padding)),
        ];
    }

    protected function expectedNormalizedRowBorderPositions(int $questionCount, int $rowCount, float $scale): array
    {
        $positions = [];

        for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
            $cell = BlankScanLayout::answerCellMm($questionCount, $rowIndex, 0);
            $rect = $this->mmSquareToPixelRect($cell['left'], $cell['top'], $cell['size'], $scale);
            $positions[] = $rect['y'];
            $positions[] = $rect['y'] + $rect['height'];
        }

        return $positions;
    }

    protected function expectedNormalizedColumnBorderPositions(int $questionCount, float $scale): array
    {
        $positions = [];

        for ($optionIndex = 0; $optionIndex < BlankScanLayout::ANSWER_OPTION_COUNT; $optionIndex++) {
            $cell = BlankScanLayout::answerCellMm($questionCount, 0, $optionIndex);
            $rect = $this->mmSquareToPixelRect($cell['left'], $cell['top'], $cell['size'], $scale);
            $positions[] = $rect['x'];
            $positions[] = $rect['x'] + $rect['width'];
        }

        return $positions;
    }

    protected function buildNormalizedHorizontalProfile($image, array $area): array
    {
        $profile = [];
        $stepX = 1;

        for ($y = $area['top']; $y <= $area['bottom']; $y++) {
            $score = 0.0;

            for ($x = $area['left']; $x <= $area['right']; $x += $stepX) {
                $darkness = $this->pixelDarkness($image, $x, $y);

                if ($darkness > 0.28) {
                    $score += $darkness;
                }
            }

            $profile[$y] = $score;
        }

        return $this->smoothProfile($profile, 2);
    }

    protected function buildNormalizedVerticalProfile($image, array $area): array
    {
        $profile = [];
        $stepY = 1;

        for ($x = $area['left']; $x <= $area['right']; $x++) {
            $score = 0.0;

            for ($y = $area['top']; $y <= $area['bottom']; $y += $stepY) {
                $darkness = $this->pixelDarkness($image, $x, $y);

                if ($darkness > 0.28) {
                    $score += $darkness;
                }
            }

            $profile[$x] = $score;
        }

        return $this->smoothProfile($profile, 2);
    }

    protected function smoothProfile(array $profile, int $radius): array
    {
        $keys = array_keys($profile);
        $smoothed = [];

        foreach ($keys as $key) {
            $sum = 0.0;
            $count = 0;

            for ($offset = -$radius; $offset <= $radius; $offset++) {
                $neighbor = $key + $offset;

                if (!array_key_exists($neighbor, $profile)) {
                    continue;
                }

                $sum += $profile[$neighbor];
                $count++;
            }

            $smoothed[$key] = $count > 0 ? ($sum / $count) : ($profile[$key] ?? 0.0);
        }

        return $smoothed;
    }

    protected function locateProfilePeak(array $profile, int $expectedPosition, int $margin): int
    {
        $positions = array_keys($profile);

        if ($positions === []) {
            return $expectedPosition;
        }

        $minPosition = (int) min($positions);
        $maxPosition = (int) max($positions);
        $from = max($minPosition, $expectedPosition - $margin);
        $to = min($maxPosition, $expectedPosition + $margin);
        $bestPosition = $expectedPosition;
        $bestScore = -1.0;

        for ($position = $from; $position <= $to; $position++) {
            $score = $profile[$position] ?? 0.0;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPosition = $position;
            }
        }

        if ($bestScore <= 0.0) {
            return $expectedPosition;
        }

        $threshold = $bestScore * 0.92;
        $runStart = $bestPosition;
        $runEnd = $bestPosition;

        while ($runStart > $from && (($profile[$runStart - 1] ?? 0.0) >= $threshold)) {
            $runStart--;
        }

        while ($runEnd < $to && (($profile[$runEnd + 1] ?? 0.0) >= $threshold)) {
            $runEnd++;
        }

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        for ($position = $runStart; $position <= $runEnd; $position++) {
            $score = $profile[$position] ?? 0.0;
            $weightedSum += $position * $score;
            $weightTotal += $score;
        }

        if ($weightTotal <= 0.0) {
            return $bestPosition;
        }

        return (int) round($weightedSum / $weightTotal);
    }

    protected function detectBorderPositionsFromProfile(array $profile, array $expectedPositions, int $margin): array
    {
        return array_map(
            fn (int $expectedPosition) => $this->locateProfilePeak($profile, $expectedPosition, $margin),
            $expectedPositions
        );
    }

    protected function alignDetectedBorderPositions(array $expectedPositions, array $detectedPositions): array
    {
        $transform = $this->fitAxisTransform($expectedPositions, $detectedPositions);

        if (!$transform) {
            return $detectedPositions;
        }

        return array_map(
            fn (int $expectedPosition) => (int) round($transform['offset'] + ($transform['scale'] * $expectedPosition)),
            $expectedPositions
        );
    }

    protected function fitAxisTransform(array $expectedPositions, array $detectedPositions): ?array
    {
        if (count($expectedPositions) !== count($detectedPositions) || count($expectedPositions) < 2) {
            return null;
        }

        $count = count($expectedPositions);
        $sumExpected = array_sum($expectedPositions);
        $sumDetected = array_sum($detectedPositions);
        $sumExpectedSquared = 0.0;
        $sumExpectedDetected = 0.0;

        foreach ($expectedPositions as $index => $expectedPosition) {
            $sumExpectedSquared += $expectedPosition * $expectedPosition;
            $sumExpectedDetected += $expectedPosition * $detectedPositions[$index];
        }

        $denominator = ($count * $sumExpectedSquared) - ($sumExpected * $sumExpected);

        if (abs($denominator) < 0.0001) {
            return null;
        }

        $scale = (($count * $sumExpectedDetected) - ($sumExpected * $sumDetected)) / $denominator;
        $offset = ($sumDetected - ($scale * $sumExpected)) / $count;

        if ($scale < 0.9 || $scale > 1.1) {
            return null;
        }

        return [
            'offset' => $offset,
            'scale' => $scale,
        ];
    }

    protected function searchNormalizedVerticalBorder($image, int $expectedX, int $y, int $height, int $margin): int
    {
        $bestX = $expectedX;
        $bestScore = -1.0;
        $fromX = max(0, $expectedX - $margin);
        $toX = min(\imagesx($image) - 2, $expectedX + $margin);
        $sampleY = max(0, $y + 1);
        $sampleHeight = max(1, $height - 2);

        for ($candidateX = $fromX; $candidateX <= $toX; $candidateX++) {
            $score = $this->averageDarkness($image, max(0, $candidateX - 1), $sampleY, 3, $sampleHeight);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestX = $candidateX;
            }
        }

        return $bestX;
    }

    protected function searchNormalizedHorizontalBorder($image, int $x, int $expectedY, int $width, int $margin): int
    {
        $bestY = $expectedY;
        $bestScore = -1.0;
        $fromY = max(0, $expectedY - $margin);
        $toY = min(\imagesy($image) - 2, $expectedY + $margin);
        $sampleX = max(0, $x + 1);
        $sampleWidth = max(1, $width - 2);

        for ($candidateY = $fromY; $candidateY <= $toY; $candidateY++) {
            $score = $this->averageDarkness($image, $sampleX, max(0, $candidateY - 1), $sampleWidth, 3);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestY = $candidateY;
            }
        }

        return $bestY;
    }

    protected function detectNormalizedAnswerGrid($normalizedPage, int $questionCount, float $scale): array
    {
        $cacheKey = spl_object_id($normalizedPage) . ':' . $questionCount . ':' . $scale;

        if (isset($this->normalizedAnswerGridCache[$cacheKey])) {
            return $this->normalizedAnswerGridCache[$cacheKey];
        }

        $rowCount = max(1, min(BlankScanLayout::questionsPerPage(), $questionCount));
        $area = $this->normalizedAnswerFieldArea($questionCount, $rowCount, $scale);
        $rowExpectedBorders = $this->expectedNormalizedRowBorderPositions($questionCount, $rowCount, $scale);
        $columnExpectedBorders = $this->expectedNormalizedColumnBorderPositions($questionCount, $scale);
        $margin = max(3, (int) round($scale * 0.9));
        $horizontalProfile = $this->buildNormalizedHorizontalProfile($normalizedPage, $area);
        $verticalProfile = $this->buildNormalizedVerticalProfile($normalizedPage, $area);
        $detectedRowBorders = $this->detectBorderPositionsFromProfile($horizontalProfile, $rowExpectedBorders, $margin);
        $detectedColumnBorders = $this->detectBorderPositionsFromProfile($verticalProfile, $columnExpectedBorders, $margin);
        $rowTransform = $this->fitAxisTransform($rowExpectedBorders, $detectedRowBorders);
        $columnTransform = $this->fitAxisTransform($columnExpectedBorders, $detectedColumnBorders);
        $rowBorders = $rowTransform
            ? array_map(
                fn (int $expectedPosition) => (int) round($rowTransform['offset'] + ($rowTransform['scale'] * $expectedPosition)),
                $rowExpectedBorders
            )
            : $this->alignDetectedBorderPositions($rowExpectedBorders, $detectedRowBorders);
        $columnBorders = $columnTransform
            ? array_map(
                fn (int $expectedPosition) => (int) round($columnTransform['offset'] + ($columnTransform['scale'] * $expectedPosition)),
                $columnExpectedBorders
            )
            : $this->alignDetectedBorderPositions($columnExpectedBorders, $detectedColumnBorders);

        $rows = [];
        for ($rowIndex = 0; $rowIndex < $rowCount; $rowIndex++) {
            $expectedCell = BlankScanLayout::answerCellMm($questionCount, $rowIndex, 0);
            $expectedRect = $this->mmSquareToPixelRect($expectedCell['left'], $expectedCell['top'], $expectedCell['size'], $scale);
            $borderIndex = $rowIndex * 2;
            $top = $rowTransform
                ? (int) round($rowTransform['offset'] + ($rowTransform['scale'] * $expectedRect['y']))
                : ($rowBorders[$borderIndex] ?? ($rowExpectedBorders[$borderIndex] ?? 0));
            $height = $rowTransform
                ? max(1, (int) round($expectedRect['height'] * $rowTransform['scale']))
                : max(1, (($rowBorders[$borderIndex + 1] ?? ($rowExpectedBorders[$borderIndex + 1] ?? ($top + 1))) - $top));
            $rows[$rowIndex] = [
                'top' => $top,
                'bottom' => $top + $height,
            ];
        }

        $columns = [];
        for ($optionIndex = 0; $optionIndex < BlankScanLayout::ANSWER_OPTION_COUNT; $optionIndex++) {
            $expectedCell = BlankScanLayout::answerCellMm($questionCount, 0, $optionIndex);
            $expectedRect = $this->mmSquareToPixelRect($expectedCell['left'], $expectedCell['top'], $expectedCell['size'], $scale);
            $borderIndex = $optionIndex * 2;
            $left = $columnTransform
                ? (int) round($columnTransform['offset'] + ($columnTransform['scale'] * $expectedRect['x']))
                : ($columnBorders[$borderIndex] ?? ($columnExpectedBorders[$borderIndex] ?? 0));
            $width = $columnTransform
                ? max(1, (int) round($expectedRect['width'] * $columnTransform['scale']))
                : max(1, (($columnBorders[$borderIndex + 1] ?? ($columnExpectedBorders[$borderIndex + 1] ?? ($left + 1))) - $left));
            $columns[$optionIndex] = [
                'left' => $left,
                'right' => $left + $width,
            ];
        }

        return $this->normalizedAnswerGridCache[$cacheKey] = [
            'rows' => $rows,
            'columns' => $columns,
        ];
    }

    protected function normalizePageImage($image, array $markers, ?array $projectionCalibration = null, float $scale = self::OCR_NORMALIZED_SCALE)
    {
        $width = max(1, (int) round(BlankScanLayout::PAGE_WIDTH_MM * $scale));
        $height = max(1, (int) round(BlankScanLayout::PAGE_HEIGHT_MM * $scale));
        $normalized = \imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            $yMm = $y / $scale;

            for ($x = 0; $x < $width; $x++) {
                $xMm = $x / $scale;
                [$sourceX, $sourceY] = $this->projectMmToPixelCalibrated($markers, $xMm, $yMm, $projectionCalibration);
                $sampleX = max(0, min(\imagesx($image) - 1, (int) round($sourceX)));
                $sampleY = max(0, min(\imagesy($image) - 1, (int) round($sourceY)));
                \imagesetpixel($normalized, $x, $y, \imagecolorat($image, $sampleX, $sampleY));
            }
        }

        return $normalized;
    }

    protected function projectSquareMmToPixelQuad(array $markers, float $leftMm, float $topMm, float $sizeMm, ?array $projectionCalibration = null): array
    {
        $topLeft = $this->projectMmToPixelCalibrated($markers, $leftMm, $topMm, $projectionCalibration);
        $topRight = $this->projectMmToPixelCalibrated($markers, $leftMm + $sizeMm, $topMm, $projectionCalibration);
        $bottomRight = $this->projectMmToPixelCalibrated($markers, $leftMm + $sizeMm, $topMm + $sizeMm, $projectionCalibration);
        $bottomLeft = $this->projectMmToPixelCalibrated($markers, $leftMm, $topMm + $sizeMm, $projectionCalibration);

        $points = [
            ['x' => $topLeft[0], 'y' => $topLeft[1]],
            ['x' => $topRight[0], 'y' => $topRight[1]],
            ['x' => $bottomRight[0], 'y' => $bottomRight[1]],
            ['x' => $bottomLeft[0], 'y' => $bottomLeft[1]],
        ];

        $xValues = array_column($points, 'x');
        $yValues = array_column($points, 'y');
        $left = (int) floor(min($xValues));
        $top = (int) floor(min($yValues));
        $right = (int) ceil(max($xValues));
        $bottom = (int) ceil(max($yValues));

        return [
            'x' => $left,
            'y' => $top,
            'width' => max(1, $right - $left),
            'height' => max(1, $bottom - $top),
            'points' => array_map(
                fn (array $point) => [
                    'x' => (float) $point['x'],
                    'y' => (float) $point['y'],
                ],
                $points,
            ),
        ];
    }

    protected function projectMmToPixelCalibrated(array $markers, float $xMm, float $yMm, ?array $projectionCalibration = null): array
    {
        [$x, $y] = $this->projectMmToPixel($markers, $xMm, $yMm);

        if (!$projectionCalibration) {
            return [$x, $y];
        }

        $u = $xMm / BlankScanLayout::PAGE_WIDTH_MM;
        $v = $yMm / BlankScanLayout::PAGE_HEIGHT_MM;
        $dx = $this->evaluatePlane($projectionCalibration['dx'] ?? null, $u, $v);
        $dy = $this->evaluatePlane($projectionCalibration['dy'] ?? null, $u, $v);

        return [$x + $dx, $y + $dy];
    }

    protected function averageDarknessInWindow($image, array $window): float
    {
        return $this->sampleWindow($image, $window, function (float $darkness, int &$darkPixels, float &$darknessTotal): void {
            $darknessTotal += $darkness;
        });
    }

    protected function darkPixelRatioInWindow($image, array $window): float
    {
        return $this->sampleWindow($image, $window, function (float $darkness, int &$darkPixels, float &$darknessTotal): void {
            if ($darkness > 0.32) {
                $darkPixels++;
            }
        }, true);
    }

    protected function sampleWindow($image, array $window, callable $collector, bool $returnRatio = false): float
    {
        $x = (int) ($window['x'] ?? 0);
        $y = (int) ($window['y'] ?? 0);
        $width = max(1, (int) ($window['width'] ?? 1));
        $height = max(1, (int) ($window['height'] ?? 1));
        $points = $window['points'] ?? null;
        $darkPixels = 0;
        $darknessTotal = 0.0;
        $count = 0;
        $stepX = max(1, (int) floor($width / 16));
        $stepY = max(1, (int) floor($height / 16));

        for ($yy = $y; $yy < $y + $height; $yy += $stepY) {
            for ($xx = $x; $xx < $x + $width; $xx += $stepX) {
                $sampleX = min($x + $width - 1, $xx + ($stepX / 2));
                $sampleY = min($y + $height - 1, $yy + ($stepY / 2));

                if ($points && !$this->pointInPolygon($sampleX, $sampleY, $points)) {
                    continue;
                }

                $count++;
                $darkness = $this->pixelDarkness($image, (int) round($sampleX), (int) round($sampleY));
                $collector($darkness, $darkPixels, $darknessTotal);
            }
        }

        if ($count === 0) {
            return 0.0;
        }

        return $returnRatio ? ($darkPixels / $count) : ($darknessTotal / $count);
    }

    protected function buildProjectionCalibration($image, array $markers, ?string $bitString = null): ?array
    {
        $bitString ??= $this->decodeBitString($image, $markers);
        $samples = $this->projectionCalibrationCornerSamples();

        for ($index = 0; $index < BlankScanLayout::CODE_BITS; $index++) {
            if (($bitString[$index] ?? '0') !== '1') {
                continue;
            }

            $cell = BlankScanLayout::codeCellMm($index);
            $centerMmX = $cell['left'] + ($cell['width'] / 2);
            $centerMmY = $cell['top'] + ($cell['height'] / 2);
            [$expectedX, $expectedY] = $this->projectMmToPixel($markers, $centerMmX, $centerMmY);
            $pixelWindow = $this->projectSquareMmToPixelQuad($markers, $cell['left'], $cell['top'], $cell['width']);
            $actualCenter = $this->detectDarkBlobCenter($image, $pixelWindow);

            if (!$actualCenter) {
                continue;
            }

            $samples[] = [
                'u' => $centerMmX / BlankScanLayout::PAGE_WIDTH_MM,
                'v' => $centerMmY / BlankScanLayout::PAGE_HEIGHT_MM,
                'dx' => $actualCenter['x'] - $expectedX,
                'dy' => $actualCenter['y'] - $expectedY,
            ];
        }

        if (count($samples) < 8) {
            return null;
        }

        $dxPlane = $this->fitCorrectionPlane($samples, 'dx');
        $dyPlane = $this->fitCorrectionPlane($samples, 'dy');

        if (!$dxPlane || !$dyPlane) {
            return null;
        }

        return [
            'dx' => $dxPlane,
            'dy' => $dyPlane,
        ];
    }

    protected function projectionCalibrationCornerSamples(): array
    {
        return array_values(array_map(
            fn (array $point) => [
                'u' => $point['x'] / BlankScanLayout::PAGE_WIDTH_MM,
                'v' => $point['y'] / BlankScanLayout::PAGE_HEIGHT_MM,
                'dx' => 0.0,
                'dy' => 0.0,
            ],
            BlankScanLayout::markerCentersMm()
        ));
    }

    protected function detectDarkBlobCenter($image, array $window): ?array
    {
        $x = max(0, $window['x'] - 6);
        $y = max(0, $window['y'] - 6);
        $width = min(imagesx($image) - $x, $window['width'] + 12);
        $height = min(imagesy($image) - $y, $window['height'] + 12);
        $weightTotal = 0.0;
        $xTotal = 0.0;
        $yTotal = 0.0;

        for ($yy = $y; $yy < $y + $height; $yy++) {
            for ($xx = $x; $xx < $x + $width; $xx++) {
                $darkness = $this->pixelDarkness($image, $xx, $yy);

                if ($darkness < 0.55) {
                    continue;
                }

                $weight = $darkness * $darkness;
                $weightTotal += $weight;
                $xTotal += $xx * $weight;
                $yTotal += $yy * $weight;
            }
        }

        if ($weightTotal <= 0.0) {
            return null;
        }

        return [
            'x' => $xTotal / $weightTotal,
            'y' => $yTotal / $weightTotal,
        ];
    }

    protected function fitCorrectionPlane(array $samples, string $valueKey): ?array
    {
        $n = 0.0;
        $sumU = 0.0;
        $sumV = 0.0;
        $sumUU = 0.0;
        $sumUV = 0.0;
        $sumVV = 0.0;
        $sumValue = 0.0;
        $sumValueU = 0.0;
        $sumValueV = 0.0;

        foreach ($samples as $sample) {
            $u = (float) ($sample['u'] ?? 0.0);
            $v = (float) ($sample['v'] ?? 0.0);
            $value = (float) ($sample[$valueKey] ?? 0.0);
            $n += 1.0;
            $sumU += $u;
            $sumV += $v;
            $sumUU += $u * $u;
            $sumUV += $u * $v;
            $sumVV += $v * $v;
            $sumValue += $value;
            $sumValueU += $u * $value;
            $sumValueV += $v * $value;
        }

        return $this->solveLinearSystem3x3(
            [
                [$n, $sumU, $sumV, $sumValue],
                [$sumU, $sumUU, $sumUV, $sumValueU],
                [$sumV, $sumUV, $sumVV, $sumValueV],
            ]
        );
    }

    protected function solveLinearSystem3x3(array $matrix): ?array
    {
        for ($pivot = 0; $pivot < 3; $pivot++) {
            $bestRow = $pivot;

            for ($row = $pivot + 1; $row < 3; $row++) {
                if (abs($matrix[$row][$pivot]) > abs($matrix[$bestRow][$pivot])) {
                    $bestRow = $row;
                }
            }

            if (abs($matrix[$bestRow][$pivot]) < 0.000001) {
                return null;
            }

            if ($bestRow !== $pivot) {
                [$matrix[$pivot], $matrix[$bestRow]] = [$matrix[$bestRow], $matrix[$pivot]];
            }

            $pivotValue = $matrix[$pivot][$pivot];

            for ($column = $pivot; $column < 4; $column++) {
                $matrix[$pivot][$column] /= $pivotValue;
            }

            for ($row = 0; $row < 3; $row++) {
                if ($row === $pivot) {
                    continue;
                }

                $factor = $matrix[$row][$pivot];

                for ($column = $pivot; $column < 4; $column++) {
                    $matrix[$row][$column] -= $factor * $matrix[$pivot][$column];
                }
            }
        }

        return [$matrix[0][3], $matrix[1][3], $matrix[2][3]];
    }

    protected function evaluatePlane(?array $coefficients, float $u, float $v): float
    {
        if (!$coefficients || count($coefficients) !== 3) {
            return 0.0;
        }

        return ($coefficients[0] ?? 0.0)
            + (($coefficients[1] ?? 0.0) * $u)
            + (($coefficients[2] ?? 0.0) * $v);
    }

    protected function pointInPolygon(float $x, float $y, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);

        if ($count < 3) {
            return false;
        }

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) $polygon[$i]['x'];
            $yi = (float) $polygon[$i]['y'];
            $xj = (float) $polygon[$j]['x'];
            $yj = (float) $polygon[$j]['y'];

            $intersects = (($yi > $y) !== ($yj > $y))
                && ($x < (($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 0.000001)) + $xi);

            if ($intersects) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    protected function normalizePixelRect(float $x1, float $y1, float $x2, float $y2): array
    {
        $left = (int) floor(min($x1, $x2));
        $top = (int) floor(min($y1, $y2));
        $width = (int) max(1, round(abs($x2 - $x1)));
        $height = (int) max(1, round(abs($y2 - $y1)));

        return [
            'x' => $left,
            'y' => $top,
            'width' => $width,
            'height' => $height,
        ];
    }

    protected function refineAnswerCellPixelRect($image, array $rect): array
    {
        $searchMarginX = max(2, (int) round($rect['width'] * 0.35));
        $searchMarginY = max(2, (int) round($rect['height'] * 0.35));

        $left = $this->searchStrongVerticalEdge(
            $image,
            $rect['x'],
            $rect['y'],
            $rect['height'],
            $searchMarginX
        );
        $right = $this->searchStrongVerticalEdge(
            $image,
            $rect['x'] + $rect['width'],
            $rect['y'],
            $rect['height'],
            $searchMarginX
        );
        $top = $this->searchStrongHorizontalEdge(
            $image,
            $rect['x'],
            $rect['y'],
            $rect['width'],
            $searchMarginY
        );
        $bottom = $this->searchStrongHorizontalEdge(
            $image,
            $rect['x'],
            $rect['y'] + $rect['height'],
            $rect['width'],
            $searchMarginY
        );

        $refined = [
            'x' => min($left, $right),
            'y' => min($top, $bottom),
            'width' => abs($right - $left),
            'height' => abs($bottom - $top),
        ];

        if (
            $refined['width'] < max(2, (int) round($rect['width'] * 0.5))
            || $refined['width'] > (int) round($rect['width'] * 1.6)
            || $refined['height'] < max(2, (int) round($rect['height'] * 0.5))
            || $refined['height'] > (int) round($rect['height'] * 1.6)
        ) {
            return $rect;
        }

        return $refined;
    }

    protected function searchStrongVerticalEdge($image, int $expectedX, int $y, int $height, int $margin): int
    {
        $bestX = $expectedX;
        $bestScore = -1.0;
        $fromX = max(0, $expectedX - $margin);
        $toX = min(\imagesx($image) - 2, $expectedX + $margin);
        $sampleY = max(0, $y + 1);
        $sampleHeight = max(1, $height - 2);

        for ($candidateX = $fromX; $candidateX <= $toX; $candidateX++) {
            $score = $this->averageDarkness($image, $candidateX, $sampleY, 2, $sampleHeight);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestX = $candidateX;
            }
        }

        return $bestX;
    }

    protected function searchStrongHorizontalEdge($image, int $x, int $expectedY, int $width, int $margin): int
    {
        $bestY = $expectedY;
        $bestScore = -1.0;
        $fromY = max(0, $expectedY - $margin);
        $toY = min(\imagesy($image) - 2, $expectedY + $margin);
        $sampleX = max(0, $x + 1);
        $sampleWidth = max(1, $width - 2);

        for ($candidateY = $fromY; $candidateY <= $toY; $candidateY++) {
            $score = $this->averageDarkness($image, $sampleX, $candidateY, $sampleWidth, 2);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestY = $candidateY;
            }
        }

        return $bestY;
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
        return $this->darkPixelRatioWithThreshold($image, $x, $y, $width, $height, 0.32);
    }

    protected function darkPixelRatioWithThreshold($image, int $x, int $y, int $width, int $height, float $threshold): float
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

                if ($this->pixelDarkness($image, $xx, $yy) > $threshold) {
                    $darkPixels++;
                }
            }
        }

        return $count > 0 ? $darkPixels / $count : 0.0;
    }

    protected function inkSignal($image, int $x, int $y, int $width, int $height): float
    {
        $width = max(1, $width);
        $height = max(1, $height);
        $sum = 0.0;
        $count = 0;
        $stepX = max(1, (int) floor($width / 16));
        $stepY = max(1, (int) floor($height / 16));

        for ($yy = $y; $yy < $y + $height; $yy += $stepY) {
            for ($xx = $x; $xx < $x + $width; $xx += $stepX) {
                $count++;
                $sum += $this->pixelInkSignal($image, $xx, $yy);
            }
        }

        return $count > 0 ? $sum / $count : 0.0;
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

    protected function pixelInkSignal($image, int $x, int $y): float
    {
        $x = max(0, min(\imagesx($image) - 1, $x));
        $y = max(0, min(\imagesy($image) - 1, $y));

        $rgb = \imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $gray = ($r * 0.299) + ($g * 0.587) + ($b * 0.114);
        $darkness = (255 - $gray) / 255;
        $chroma = ($max - $min) / 255;
        $blueBias = max(0, $b - max($r, $g)) / 255;

        return max($chroma * 0.7, $blueBias) * $darkness;
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
