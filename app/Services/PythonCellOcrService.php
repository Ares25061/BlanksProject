<?php

namespace App\Services;

use App\Support\UnifiedSheetLayout;
use App\Support\Utf8Normalizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\ExecutableFinder;
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
                'scan' => 'Python OCR не вернул идентификатор QR-кода страницы.',
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
                'scan' => 'Python OCR вернул неожиданный ответ.',
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
                'scan' => 'Python-рендерер PDF не вернул путь к выходному файлу.',
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
                'scan' => 'Не настроен entrypoint для Python OCR.',
            ]);
        }

        $requestPath = tempnam(sys_get_temp_dir(), 'blank-ocr-request-');

        if ($requestPath === false) {
            throw ValidationException::withMessages([
                'scan' => 'Не удалось создать временный файл для Python OCR.',
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

            try {
                $process->run();
            } catch (\Throwable $exception) {
                Log::warning('Python OCR process failed to start.', [
                    'operation' => $request['operation'] ?? null,
                    'python' => $python,
                    'entrypoint' => $entrypoint,
                    'image_path' => $request['image_path'] ?? null,
                    'output_path' => $request['output_path'] ?? null,
                    'exception' => Utf8Normalizer::string($exception->getMessage()),
                ]);

                throw ValidationException::withMessages([
                    'scan' => Utf8Normalizer::string(
                        'Не удалось запустить процесс Python OCR: ' . ($exception->getMessage() ?: 'неизвестная ошибка')
                    ),
                ]);
            }

            if (!$process->isSuccessful()) {
                $stderr = trim($process->getErrorOutput());
                $stdout = trim($process->getOutput());
                $message = Utf8Normalizer::string($stderr !== '' ? $stderr : $stdout);

                if ($message === '') {
                    $details = [];
                    $exitCode = $process->getExitCode();

                    if ($exitCode !== null) {
                        $details[] = 'exit code ' . $exitCode;
                    }

                    if (method_exists($process, 'hasBeenSignaled') && $process->hasBeenSignaled()) {
                        $signal = $process->getTermSignal();

                        if ($signal !== null) {
                            $details[] = 'signal ' . $signal;
                        }
                    }

                    $message = 'Сбой Python OCR';

                    if (!empty($request['operation'])) {
                        $message .= ' во время операции ' . $request['operation'];
                    }

                    if ($details !== []) {
                        $message .= ' (' . implode(', ', $details) . ')';
                    }

                    $message .= '. Подробности смотрите в логах сервера (stderr/stdout процесса).';
                }

                Log::warning('Python OCR process exited unsuccessfully.', [
                    'operation' => $request['operation'] ?? null,
                    'python' => $python,
                    'entrypoint' => $entrypoint,
                    'image_path' => $request['image_path'] ?? null,
                    'output_path' => $request['output_path'] ?? null,
                    'exit_code' => $process->getExitCode(),
                    'stderr' => Utf8Normalizer::string($stderr),
                    'stdout' => Utf8Normalizer::string($stdout),
                    'has_manifest' => array_key_exists('manifest', $request),
                    'request_meta' => Arr::except($request, ['manifest']),
                ]);

                throw ValidationException::withMessages([
                    'scan' => Utf8Normalizer::string('Ошибка Python OCR: ' . $message),
                ]);
            }

            $rawOutput = Utf8Normalizer::string($process->getOutput()) ?? '';
            $payload = json_decode($rawOutput, true);

            if (!is_array($payload)) {
                throw ValidationException::withMessages([
                    'scan' => 'Python OCR вернул некорректный JSON.',
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
        $configured = trim($this->configuredPythonCandidate());
        $projectVenv = trim($this->projectVenvPythonCandidate());

        if ($configured !== '' && $this->isWindowsStoreAlias($configured)) {
            throw ValidationException::withMessages([
                'scan' => 'PADDLE_OCR_PYTHON указывает на алиас Microsoft Store. Укажите путь к реальному интерпретатору, например: ' . $projectVenv,
            ]);
        }

        $candidates = array_values(array_filter(array_unique(array_merge(
            $configured !== '' ? [$configured] : [],
            $projectVenv !== '' ? [$projectVenv] : [],
            $this->fallbackPythonCandidates(),
        ))));

        foreach ($candidates as $candidate) {
            $resolved = $this->resolvePythonCandidate($candidate);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        throw ValidationException::withMessages([
            'scan' => Utf8Normalizer::string(
                'Интерпретатор Python для OCR не найден. Проверены пути: ' . implode(', ', $candidates)
            ),
        ]);
    }

    protected function isWindowsStoreAlias(string $python): bool
    {
        $normalized = str_replace('/', '\\', strtolower(trim($python)));

        return str_contains($normalized, '\\appdata\\local\\microsoft\\windowsapps\\python.exe');
    }

    protected function configuredPythonCandidate(): string
    {
        return (string) config('services.paddle_ocr.python', '');
    }

    protected function projectVenvPythonCandidate(): string
    {
        return base_path(DIRECTORY_SEPARATOR === '\\' ? '.venv\\Scripts\\python.exe' : '.venv/bin/python');
    }

    protected function fallbackPythonCandidates(): array
    {
        return DIRECTORY_SEPARATOR === '\\'
            ? ['py', 'python']
            : ['/app/.venv/bin/python', '/mise/shims/python', '/usr/local/bin/python3', '/usr/bin/python3', 'python3', 'python'];
    }

    protected function resolvePythonCandidate(string $candidate): ?string
    {
        $candidate = trim($candidate);

        if ($candidate === '' || $this->isWindowsStoreAlias($candidate)) {
            return null;
        }

        if ($this->looksLikePath($candidate)) {
            return is_file($candidate) ? $candidate : null;
        }

        return (new ExecutableFinder())->find($candidate) ?: null;
    }

    protected function looksLikePath(string $candidate): bool
    {
        return str_contains($candidate, '/')
            || str_contains($candidate, '\\')
            || preg_match('/^[A-Za-z]:/', $candidate) === 1;
    }
}
