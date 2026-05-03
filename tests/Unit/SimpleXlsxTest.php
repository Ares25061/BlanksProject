<?php

namespace Tests\Unit;

use App\Support\SimpleXlsx;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SimpleXlsxTest extends TestCase
{
    public function test_stored_zip_fallback_writes_readable_utf8_workbook(): void
    {
        $reflection = new ReflectionClass(SimpleXlsx::class);
        $entriesMethod = $reflection->getMethod('workbookEntries');
        $writeMethod = $reflection->getMethod('writeStoredZip');
        $escapeMethod = $reflection->getMethod('escapeXml');

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('simple_xlsx_fallback_', true).'.xlsx';
        $entries = $entriesMethod->invoke(null, now()->toIso8601String(), $escapeMethod->invoke(null, 'Тест'), [
            ['question_text', 'order', 'variant'],
            ['Какой цикл гарантированно выполнится?', '1', '1'],
            ['Что означает break?', '2', '2'],
        ]);

        $writeMethod->invoke(null, $path, $entries);

        try {
            $this->assertSame([
                ['question_text', 'order', 'variant'],
                ['Какой цикл гарантированно выполнится?', '1', '1'],
                ['Что означает break?', '2', '2'],
            ], SimpleXlsx::readRows($path));
        } finally {
            @unlink($path);
        }
    }
}
