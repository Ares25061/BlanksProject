<?php

namespace App\Services;

use App\Models\BlankForm;
use App\Models\Test;
use App\Support\Utf8Normalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BlankScanService
{
    public function __construct(
        private BlankFormService $blankFormService,
        private GradingService $gradingService,
        private ScanPreviewService $scanPreviewService,
        private BlankSheetManifestService $blankSheetManifestService,
        private PythonCellOcrService $pythonCellOcrService,
        private BlankSheetQrCodeService $blankSheetQrCodeService,
        private TestVariantService $testVariantService,
    ) {
    }

    public function scanUploadedForms(Test $test, array $files): array
    {
        $this->prepareLongRunningScan();

        $results = collect($files)
            ->map(fn (UploadedFile $file) => $this->scanUploadedPage($test, $file))
            ->groupBy('processing_key')
            ->map(fn ($pageScans) => $this->finalizeGroupedScan($pageScans))
            ->values()
            ->all();

        return Utf8Normalizer::deep($results);
    }

    protected function scanUploadedPage(Test $test, UploadedFile $file): array
    {
        $image = $this->loadImage($file);

        try {
            $scanPath = $this->storeNormalizedScanImage($image);
            $absoluteScanPath = Storage::disk('local')->path($scanPath);
            $identified = Utf8Normalizer::deep($this->pythonCellOcrService->identifyPage($absoluteScanPath));
            $pagePayload = $this->blankSheetQrCodeService->normalizePayload((array) ($identified['qr_payload'] ?? []));

            if (!$pagePayload) {
                throw ValidationException::withMessages([
                    'scan' => 'Could not recognize the page QR code. Make sure the page markers and the top QR are visible.',
                ]);
            }

            $blankForm = BlankForm::with(['test.questions.answers', 'studentGroup', 'groupStudent'])
                ->findOrFail($pagePayload['blank_form_id']);
            $pages = $this->blankSheetManifestService->ensurePersisted($blankForm);
            $expectedPageCount = max(1, count($pages));
            $pageNumber = max(1, min((int) $pagePayload['page_number'], $expectedPageCount));
            $manifest = collect($pages)
                ->first(fn (array $page) => (int) ($page['page_number'] ?? 0) === $pageNumber);

            if (!$manifest) {
                throw ValidationException::withMessages([
                    'scan' => 'Could not load the page manifest for cell recognition.',
                ]);
            }

            $recognized = Utf8Normalizer::deep($this->extractAnswersViaPython($absoluteScanPath, $manifest, $pageNumber));
            $warnings = array_merge($identified['warnings'] ?? [], $recognized['warnings']);
            $isCurrentTestForm = (int) $blankForm->test_id === (int) $test->id;

            if (!$isCurrentTestForm) {
                $warnings[] = 'This scan belongs to another test. Saved only as a temporary OCR preview without grading.';
            }

            if (($pagePayload['form_number'] ?? '') !== '' && (string) $pagePayload['form_number'] !== (string) $blankForm->form_number) {
                $warnings[] = 'The QR code points to a different form number. Using the form found by ID.';
            }

            if ((int) $pagePayload['page_count'] !== $expectedPageCount) {
                $warnings[] = 'The QR page count differs from the saved manifest. Using the saved manifest.';
            }

            return [
                'file_name' => $this->normalizeFileName($file->getClientOriginalName()),
                'processing_key' => ($isCurrentTestForm ? 'blank-form:' : 'foreign-preview:') . $blankForm->id,
                'processing_mode' => $isCurrentTestForm ? 'persist' : 'foreign_preview',
                'blank_form_id' => $blankForm->id,
                'blank_form' => $blankForm,
                'form_number' => Utf8Normalizer::string($blankForm->form_number),
                'student_name' => Utf8Normalizer::string($blankForm->student_full_name),
                'group_name' => Utf8Normalizer::string($blankForm->group_name),
                'variant_number' => $blankForm->variant_number ?? 1,
                'page_number' => $pageNumber,
                'page_count' => $expectedPageCount,
                'recognized_answers' => $recognized['display_answers'],
                'question_answers' => $recognized['question_answers'],
                'warnings' => array_values(array_unique(array_filter($warnings))),
                'scan_path' => $scanPath,
                'question_range' => $recognized['question_range'],
            ];
        } finally {
            \imagedestroy($image);
        }
    }

    protected function extractAnswersViaPython(string $imagePath, array $manifest, int $pageNumber): array
    {
        $payload = Utf8Normalizer::deep($this->pythonCellOcrService->recognize($imagePath, $manifest));
        $questionAnswers = [];
        $displayAnswers = [];
        $warnings = collect($payload['warnings'] ?? [])->filter()->values()->all();

        foreach (($payload['question_results'] ?? []) as $questionResult) {
            $questionId = (int) ($questionResult['question_id'] ?? 0);
            $questionNumber = (int) ($questionResult['question_number'] ?? 0);
            $questionType = (string) ($questionResult['type'] ?? 'single');
            $selectedAnswerIds = collect($questionResult['selected_answer_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
            $selectedLetters = collect($questionResult['selected_letters'] ?? [])
                ->map(fn ($letter) => Utf8Normalizer::string(trim((string) $letter)))
                ->filter()
                ->values()
                ->all();
            $borderlineLetters = collect($questionResult['borderline_letters'] ?? [])
                ->map(fn ($letter) => Utf8Normalizer::string(trim((string) $letter)))
                ->filter()
                ->values()
                ->all();
            $borderlineSelectedLetters = collect($questionResult['borderline_selected_letters'] ?? [])
                ->map(fn ($letter) => Utf8Normalizer::string(trim((string) $letter)))
                ->filter()
                ->values()
                ->all();
            $borderlineUnselectedLetters = collect($questionResult['borderline_unselected_letters'] ?? [])
                ->map(fn ($letter) => Utf8Normalizer::string(trim((string) $letter)))
                ->filter()
                ->values()
                ->all();

            if ($questionId > 0) {
                $questionAnswers[$questionId] = $selectedAnswerIds;
            }

            if ($questionType === 'single' && count($selectedAnswerIds) > 1) {
                $warnings[] = 'A single-choice question has multiple marked cells.';
            }

            if ($borderlineUnselectedLetters !== []) {
                $warnings[] = 'Question ' . $questionNumber . ' has weak borderline marks that stayed below the threshold: ' . implode(', ', $borderlineUnselectedLetters) . '.';
            } elseif ($borderlineSelectedLetters !== [] && count($borderlineSelectedLetters) === count($selectedLetters)) {
                $warnings[] = 'Question ' . $questionNumber . ' was recognized from weak borderline marks: ' . implode(', ', $borderlineSelectedLetters) . '.';
            }

            $displayAnswers[] = [
                'question_number' => $questionNumber,
                'selected' => $selectedLetters,
                'borderline' => $borderlineLetters,
                'borderline_selected' => $borderlineSelectedLetters,
                'borderline_unselected' => $borderlineUnselectedLetters,
                'type' => $questionType,
                'page_number' => $pageNumber,
            ];
        }

        return [
            'question_answers' => $questionAnswers,
            'display_answers' => $displayAnswers,
            'warnings' => $warnings,
            'question_range' => $payload['question_range'] ?? ($manifest['question_range'] ?? null),
        ];
    }

    protected function finalizeGroupedScan($pageScans): array
    {
        $firstPage = $pageScans->first();

        if (($firstPage['processing_mode'] ?? 'persist') === 'foreign_preview') {
            return $this->finalizeForeignPreview($pageScans);
        }

        $blankForm = $firstPage['blank_form'];
        $expectedPageCount = (int) $firstPage['page_count'];
        $pagesByNumber = [];
        $warnings = [];

        foreach ($pageScans as $pageScan) {
            $pageNumber = (int) $pageScan['page_number'];

            if (isset($pagesByNumber[$pageNumber])) {
                $warnings[] = 'The same page was uploaded more than once. Using the last uploaded copy.';
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
            $warnings[] = 'Not all pages of the form were uploaded. Missing pages: ' . implode(', ', $missingPages) . '.';

            return [
                'file_name' => $this->summarizeFileNames($pagesByNumber),
                'blank_form_id' => $blankForm->id,
                'form_number' => Utf8Normalizer::string($blankForm->form_number),
                'student_name' => Utf8Normalizer::string($blankForm->student_full_name),
                'group_name' => Utf8Normalizer::string($blankForm->group_name),
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
            'form_number' => Utf8Normalizer::string($blankForm->form_number),
            'student_name' => Utf8Normalizer::string($blankForm->student_full_name),
            'group_name' => Utf8Normalizer::string($blankForm->group_name),
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
                $warnings[] = 'The same page was uploaded more than once. Using the last uploaded copy.';
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
            $warnings[] = 'Not all pages of the form were uploaded. Missing pages: ' . implode(', ', $missingPages) . '.';

            return [
                'file_name' => $this->summarizeFileNames($pagesByNumber),
                'blank_form_id' => null,
                'form_number' => Utf8Normalizer::string($blankForm->form_number),
                'student_name' => Utf8Normalizer::string($blankForm->student_full_name),
                'group_name' => Utf8Normalizer::string($blankForm->group_name),
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
            'form_number' => Utf8Normalizer::string($blankForm->form_number),
            'student_name' => Utf8Normalizer::string($blankForm->student_full_name),
            'group_name' => Utf8Normalizer::string($blankForm->group_name),
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
            return Utf8Normalizer::string((string) $fileNames->first()) ?? '';
        }

        return Utf8Normalizer::string($fileNames->implode(', ')) ?? '';
    }

    protected function normalizeFileName(string $fileName): string
    {
        $normalized = Utf8Normalizer::string($fileName) ?? 'scan-file';

        return trim($normalized) !== '' ? $normalized : 'scan-file';
    }

    protected function prepareLongRunningScan(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        if (function_exists('ini_set')) {
            @ini_set('max_execution_time', '0');
        }
    }

    protected function loadImage(UploadedFile $file)
    {
        $mimeType = $file->getMimeType();
        $path = $file->getRealPath();

        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatefrompng') || !function_exists('imagejpeg') || !function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages([
                'scan' => 'GD image extension is not enabled on the server.',
            ]);
        }

        if ($this->isPdfFile($file, $mimeType)) {
            $image = $this->loadPdfFirstPage($path);
        } else {
            if ($mimeType === 'image/webp' && !function_exists('imagecreatefromwebp')) {
                throw ValidationException::withMessages([
                    'scan' => 'WEBP support is not enabled in GD on the server.',
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
                'scan' => 'Could not open the uploaded scan image.',
            ]);
        }

        if (\imagesx($image) > \imagesy($image)) {
            $rotated = \imagerotate($image, 90, 255);
            \imagedestroy($image);
            $image = $rotated;
        }

        return $image;
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
                // fallback
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

            $outputPath = $tempPrefix . '-python.png';
            $generatedFiles[] = $outputPath;
            $renderedPath = $this->pythonCellOcrService->renderPdfFirstPage($path, $outputPath);

            if (is_file($renderedPath)) {
                $image = \imagecreatefrompng($renderedPath);
                if ($image !== false) {
                    return $image;
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
            'scan' => 'PDF was uploaded, but the first page could not be rendered. Install Imagick, pdftoppm, or keep the Python OCR environment available for PDF fallback.',
        ]);
    }

    protected function storeNormalizedScanImage($image): string
    {
        ob_start();
        \imagejpeg($image, null, 92);
        $binary = ob_get_clean();

        if ($binary === false) {
            throw ValidationException::withMessages([
                'scan' => 'Could not save the normalized scan image.',
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
                'scan' => 'Could not prepare a temporary directory for PDF processing.',
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
