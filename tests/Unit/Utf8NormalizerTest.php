<?php

namespace Tests\Unit;

use App\Support\Utf8Normalizer;
use PHPUnit\Framework\TestCase;

class Utf8NormalizerTest extends TestCase
{
    public function test_it_converts_windows_1251_strings_to_utf8(): void
    {
        $cp1251 = iconv('UTF-8', 'Windows-1251', 'св.pdf');

        $this->assertSame('св.pdf', Utf8Normalizer::string($cp1251));
    }

    public function test_it_normalizes_nested_arrays(): void
    {
        $cp1251 = iconv('UTF-8', 'Windows-1251', 'Привет');
        $normalized = Utf8Normalizer::deep([
            'message' => $cp1251,
            'items' => [$cp1251],
        ]);

        $this->assertSame('Привет', $normalized['message']);
        $this->assertSame('Привет', $normalized['items'][0]);
    }
}
