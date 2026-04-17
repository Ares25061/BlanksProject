<?php

namespace App\Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BlankSheetQrCodeService
{
    public function buildPayload(array $identity): array
    {
        $payload = [
            'v' => 1,
            'bid' => (int) ($identity['blank_form_id'] ?? 0),
            'pg' => max(1, (int) ($identity['page_number'] ?? 1)),
            'pc' => max(1, (int) ($identity['page_count'] ?? 1)),
            'fn' => (string) ($identity['form_number'] ?? ''),
        ];

        $payload['sig'] = $this->signatureFor($payload);

        return $payload;
    }

    public function normalizePayload(array $payload): ?array
    {
        if (!$this->verifyPayload($payload)) {
            return null;
        }

        return [
            'blank_form_id' => (int) Arr::get($payload, 'bid', 0),
            'page_number' => max(1, (int) Arr::get($payload, 'pg', 1)),
            'page_count' => max(1, (int) Arr::get($payload, 'pc', 1)),
            'form_number' => (string) Arr::get($payload, 'fn', ''),
        ];
    }

    public function verifyPayload(array $payload): bool
    {
        $signature = strtoupper(trim((string) Arr::get($payload, 'sig', '')));

        if ($signature === '') {
            return false;
        }

        $candidate = Arr::except($payload, ['sig']);

        return hash_equals($this->signatureFor($candidate), $signature);
    }

    public function renderDataUri(array $payload, int $sizePx = 360): string
    {
        return $this->renderTextDataUri($this->encodePayload($payload), $sizePx);
    }

    public function renderTextDataUri(string $text, int $sizePx = 360): string
    {
        $qrCode = QrCode::create($text)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize(max(180, $sizePx))
            ->setMargin(2)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);

        $writer = new SvgWriter();

        return $writer->write($qrCode)->getDataUri();
    }

    public function encodePayload(array $payload): string
    {
        return json_encode(
            $this->sortRecursive($payload),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
    }

    protected function signatureFor(array $payload): string
    {
        $secret = $this->resolveSecret();

        return strtoupper(substr(hash_hmac('sha256', $this->encodePayload($payload), $secret), 0, 20));
    }

    protected function resolveSecret(): string
    {
        $key = '';

        if (function_exists('config')) {
            try {
                $key = (string) config('app.key', '');
            } catch (\Throwable) {
                $key = '';
            }
        }

        if ($key === '') {
            $key = (string) ($_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: '');
        }

        if (Str::startsWith($key, 'base64:')) {
            $decoded = base64_decode(Str::after($key, 'base64:'), true);

            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $key !== '' ? $key : 'blank-sheet-local-secret';
    }

    protected function sortRecursive(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->sortRecursive($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->sortRecursive($item);
        }

        return $value;
    }
}
