<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ScanPreviewService
{
    public function createPreview(int $userId, array $payload): array
    {
        $token = (string) Str::uuid();
        $normalizedPayload = [
            'status' => 'success',
            'token' => $token,
            'created_by' => $userId,
            'created_at' => now()->toIso8601String(),
            'data' => $payload['data'] ?? [],
            'grade' => $payload['grade'] ?? null,
        ];

        Storage::disk('local')->put(
            $this->previewPath($token),
            json_encode($normalizedPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );

        return $normalizedPayload;
    }

    public function getPreview(string $token, int $userId): array
    {
        $path = $this->previewPath($token);
        if (!Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages([
                'preview' => 'Временный результат распознавания не найден.',
            ]);
        }

        $payload = json_decode((string) Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);

        if ((int) ($payload['created_by'] ?? 0) !== $userId) {
            throw ValidationException::withMessages([
                'preview' => 'Этот временный результат распознавания вам недоступен.',
            ]);
        }

        return $payload;
    }

    public function resolvePreviewScanPath(array $previewPayload, int $pageNumber = 1): ?string
    {
        $normalizedPageNumber = max(1, $pageNumber);
        $pages = collect(data_get($previewPayload, 'data.metadata.scan.pages', []))
            ->filter(fn ($page) => !empty($page['scan_path']))
            ->values();

        if ($pages->isNotEmpty()) {
            $matchedPage = $pages->first(fn ($page) => (int) ($page['page_number'] ?? 0) === $normalizedPageNumber);

            return $matchedPage['scan_path'] ?? ($pages->first()['scan_path'] ?? null);
        }

        return data_get($previewPayload, 'data.scan_path');
    }

    protected function previewPath(string $token): string
    {
        return 'scan-previews/' . trim($token) . '.json';
    }
}
