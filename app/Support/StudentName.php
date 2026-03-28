<?php

namespace App\Support;

class StudentName
{
    public static function parse(?string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim((string) $fullName), -1, PREG_SPLIT_NO_EMPTY);

        return [
            'last_name' => $parts[0] ?? null,
            'first_name' => $parts[1] ?? null,
            'patronymic' => count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : null,
            'full_name' => trim((string) $fullName),
        ];
    }

    public static function format(?string $lastName, ?string $firstName, ?string $patronymic = null): string
    {
        return trim(implode(' ', array_filter([$lastName, $firstName, $patronymic])));
    }
}
