<?php

namespace App\Support;

class Utf8Normalizer
{
    public static function deep(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::string($value);
        }

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[$key] = self::deep($item);
            }

            return $normalized;
        }

        return $value;
    }

    public static function string(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        foreach (['Windows-1251', 'CP1251', 'ISO-8859-1', 'ASCII'] as $encoding) {
            $converted = @mb_convert_encoding($value, 'UTF-8', $encoding);

            if (is_string($converted) && $converted !== '' && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        if (is_string($converted) && $converted !== '') {
            return $converted;
        }

        return preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
    }
}
