<?php

namespace Tests\Unit;

use App\Services\PythonCellOcrService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PythonCellOcrServiceTest extends TestCase
{
    public function test_resolve_python_executable_skips_missing_configured_path_and_uses_existing_fallback(): void
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'python-ocr-' . bin2hex(random_bytes(4));
        $fakePython = $tempDir . DIRECTORY_SEPARATOR . 'python-fallback' . (DIRECTORY_SEPARATOR === '\\' ? '.exe' : '');

        mkdir($tempDir, 0777, true);
        file_put_contents($fakePython, '#!/bin/sh' . PHP_EOL);

        try {
            $service = new class('/missing/python', $fakePython) extends PythonCellOcrService {
                public function __construct(
                    private string $configuredCandidate,
                    private string $fallbackCandidate,
                ) {
                }

                public function resolveForTest(): string
                {
                    return $this->resolvePythonExecutable();
                }

                protected function configuredPythonCandidate(): string
                {
                    return $this->configuredCandidate;
                }

                protected function projectVenvPythonCandidate(): string
                {
                    return '/missing/project/.venv/bin/python';
                }

                protected function fallbackPythonCandidates(): array
                {
                    return [$this->fallbackCandidate];
                }
            };

            $this->assertSame($fakePython, $service->resolveForTest());
        } finally {
            @unlink($fakePython);
            @rmdir($tempDir);
        }
    }

    public function test_resolve_python_executable_rejects_windows_store_alias(): void
    {
        $service = new class extends PythonCellOcrService {
            public function resolveForTest(): string
            {
                return $this->resolvePythonExecutable();
            }

            protected function configuredPythonCandidate(): string
            {
                return 'C:\\Users\\User\\AppData\\Local\\Microsoft\\WindowsApps\\python.exe';
            }

            protected function projectVenvPythonCandidate(): string
            {
                return 'C:\\GitHub\\BlanksProject\\.venv\\Scripts\\python.exe';
            }

            protected function fallbackPythonCandidates(): array
            {
                return [];
            }
        };

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Microsoft Store alias');

        $service->resolveForTest();
    }
}
