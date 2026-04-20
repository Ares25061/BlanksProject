<?php

namespace Tests\Unit;

use App\Services\PythonCellOcrService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PythonCellOcrServiceTest extends TestCase
{
    public function test_resolve_python_executable_skips_missing_configured_path_and_uses_existing_fallback(): void
    {
        $fakePython = '/resolved/python-fallback';

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

            protected function resolvePythonCandidate(string $candidate): ?string
            {
                return $candidate === $this->fallbackCandidate ? $candidate : null;
            }

            protected function missingRequiredPythonModules(string $python): array
            {
                return [];
            }
        };

        $this->assertSame($fakePython, $service->resolveForTest());
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
        $this->expectExceptionMessage('Microsoft Store');

        $service->resolveForTest();
    }

    public function test_resolve_python_executable_skips_configured_python_without_required_modules_and_uses_project_venv(): void
    {
        $configuredPython = '/usr/bin/python3';
        $projectPython = '/app/.venv/bin/python';

        $service = new class($configuredPython, $projectPython) extends PythonCellOcrService {
            public function __construct(
                private string $configuredCandidate,
                private string $projectCandidate,
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
                return $this->projectCandidate;
            }

            protected function fallbackPythonCandidates(): array
            {
                return [];
            }

            protected function resolvePythonCandidate(string $candidate): ?string
            {
                return in_array($candidate, [$this->configuredCandidate, $this->projectCandidate], true)
                    ? $candidate
                    : null;
            }

            protected function missingRequiredPythonModules(string $python): array
            {
                return $python === $this->configuredCandidate ? ['cv2'] : [];
            }
        };

        $this->assertSame($projectPython, $service->resolveForTest());
    }
}
