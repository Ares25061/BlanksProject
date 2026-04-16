<?php

namespace App\Services;

use App\Support\UnifiedSheetLayout;
use App\Support\Utf8Normalizer;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class PythonCellOcrService
{
    public function identifyPage(string $imagePath): array
    {
        $payload = $this->runPython([
            'operation' => 'identify',
            'image_path' => $imagePath,
            'marker_centers_mm' => UnifiedSheetLayout::markerCentersMm(),
            'qr_zone' => UnifiedSheetLayout::qrZoneMm(),
            'page_width_mm' => UnifiedSheetLayout::PAGE_WIDTH_MM,
            'page_height_mm' => UnifiedSheetLayout::PAGE_HEIGHT_MM,
        ]);

        if (!is_array($payload) || !array_key_exists('qr_payload', $payload)) {
            throw ValidationException::withMessages([
                'scan' => 'Python OCR did not return a page QR identifier.',
            ]);
        }

        return [
            'qr_payload' => Arr::get($payload, 'qr_payload'),
            'warnings' => Arr::get($payload, 'warnings', []),
            'alignment' => Arr::get($payload, 'alignment', []),
            'paddle' => Arr::get($payload, 'paddle', []),
        ];
    }

    public function recognize(string $imagePath, array $manifest): array
    {
        $payload = $this->runPython([
            'operation' => 'recognize',
            'image_path' => $imagePath,
            'manifest' => $manifest,
            'fill_threshold' => (float) config('services.paddle_ocr.fill_threshold', 0.38),
            'uncertain_margin' => (float) config('services.paddle_ocr.uncertain_margin', 0.06),
        ]);

        if (!is_array($payload) || !isset($payload['question_results'])) {
            throw ValidationException::withMessages([
                'scan' => 'Python OCR returned an unexpected response.',
            ]);
        }

        return [
            'question_results' => Arr::get($payload, 'question_results', []),
            'question_range' => Arr::get($payload, 'question_range'),
            'warnings' => Arr::get($payload, 'warnings', []),
            'alignment' => Arr::get($payload, 'alignment', []),
            'paddle' => Arr::get($payload, 'paddle', []),
        ];
    }

    public function renderPdfFirstPage(string $pdfPath, string $outputPath): string
    {
        $payload = $this->runPython([
            'operation' => 'render_pdf_first_page',
            'image_path' => $pdfPath,
            'output_path' => $outputPath,
        ]);

        $renderedPath = trim((string) Arr::get($payload, 'output_path', ''));

        if ($renderedPath === '') {
            throw ValidationException::withMessages([
                'scan' => 'Python PDF renderer did not return an output path.',
            ]);
        }

        return $renderedPath;
    }

    protected function runPython(array $request): array
    {
        $python = $this->resolvePythonExecutable();
        $entrypoint = trim((string) config('services.paddle_ocr.entrypoint', ''));
        $timeoutSeconds = max(10, (int) config('services.paddle_ocr.timeout', 60));

        if ($entrypoint === '') {
            throw ValidationException::withMessages([
                'scan' => 'Python OCR entrypoint is not configured.',
            ]);
        }

        $requestPath = tempnam(sys_get_temp_dir(), 'blank-ocr-request-');

        if ($requestPath === false) {
            throw ValidationException::withMessages([
                'scan' => 'Failed to create a temporary file for Python OCR.',
            ]);
        }

        try {
            file_put_contents(
                $requestPath,
                json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );

            $process = new Process(
                [$python, $entrypoint, '--request', $requestPath],
                base_path(),
                array_merge($_ENV, [
                    'PADDLE_PDX_DISABLE_MODEL_SOURCE_CHECK' => 'True',
                ]),
                null,
                $timeoutSeconds
            );

            $process->run();

            if (!$process->isSuccessful()) {
                $message = trim($process->getErrorOutput()) ?: trim($process->getOutput());
                $message = Utf8Normalizer::string($message);
                $message = $message !== '' ? $message : 'Python OCR failed.';

                throw ValidationException::withMessages([
                    'scan' => Utf8Normalizer::string('Python OCR error: ' . $message),
                ]);
            }

            $rawOutput = Utf8Normalizer::string($process->getOutput()) ?? '';
            $payload = json_decode($rawOutput, true);

            if (!is_array($payload)) {
                throw ValidationException::withMessages([
                    'scan' => 'Python OCR returned invalid JSON.',
                ]);
            }

            return Utf8Normalizer::deep($payload);
        } finally {
            if (is_file($requestPath)) {
                @unlink($requestPath);
            }
        }
    }

    protected function resolvePythonExecutable(): string
    {
        $configured = trim((string) config('services.paddle_ocr.python', ''));
        $projectVenv = base_path(DIRECTORY_SEPARATOR === '\\' ? '.venv\\Scripts\\python.exe' : '.venv/bin/python');

        if ($projectVenv !== '' && is_file($projectVenv)) {
            if ($configured === '' || $configured === 'python' || $this->isWindowsStoreAlias($configured)) {
                return $projectVenv;
            }
        }

        if ($configured !== '') {
            if ($this->isWindowsStoreAlias($configured)) {
                throw ValidationException::withMessages([
                    'scan' => 'PADDLE_OCR_PYTHON points to the Microsoft Store alias. Use a real interpreter path, for example: ' . $projectVenv,
                ]);
            }

            return $configured;
        }

        if ($projectVenv !== '' && is_file($projectVenv)) {
            return $projectVenv;
        }

        return 'python';
    }

    protected function isWindowsStoreAlias(string $python): bool
    {
        $normalized = str_replace('/', '\\', strtolower(trim($python)));

        return str_contains($normalized, '\\appdata\\local\\microsoft\\windowsapps\\python.exe');
    }
}
