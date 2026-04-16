<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class SimpleXlsx
{
    public static function readRows(string $path): array
    {
        self::ensureZipSupport();

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Не удалось открыть XLSX-файл.');
        }

        try {
            $sharedStrings = self::readSharedStrings($zip);
            $sheetPath = self::resolveFirstWorksheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);

            if ($sheetXml === false) {
                throw new RuntimeException('Не удалось прочитать первый лист XLSX-файла.');
            }

            return self::parseSheetRows($sheetXml, $sharedStrings);
        } finally {
            $zip->close();
        }
    }

    public static function writeWorkbook(string $sheetName, array $rows): string
    {
        self::ensureZipSupport();

        $directory = storage_path('framework/testing/xlsx-temp');
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Не удалось подготовить папку для временных XLSX-файлов.');
        }

        $tempPath = $directory . DIRECTORY_SEPARATOR . uniqid('blanks_xlsx_', true) . '.xlsx';

        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempPath);
            throw new RuntimeException('Не удалось собрать XLSX-файл.');
        }

        $timestamp = now()->toIso8601String();
        $sheetTitle = self::escapeXml(mb_substr(trim($sheetName) ?: 'Sheet1', 0, 31));

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::rootRelationshipsXml());
        $zip->addFromString('docProps/app.xml', self::appPropertiesXml());
        $zip->addFromString('docProps/core.xml', self::corePropertiesXml($timestamp));
        $zip->addFromString('xl/workbook.xml', self::workbookXml($sheetTitle));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::worksheetXml($rows));
        $zip->close();

        return $tempPath;
    }

    private static function ensureZipSupport(): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('На сервере недоступна поддержка ZIP, поэтому XLSX сейчас недоступен.');
        }
    }

    private static function readSharedStrings(ZipArchive $zip): array
    {
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml === false) {
            return [];
        }

        $xml = self::loadXml($sharedStringsXml);
        $namespaces = $xml->getNamespaces(true);
        $mainNamespace = $namespaces[''] ?? null;
        $xml->registerXPathNamespace('main', $mainNamespace ?: 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $items = [];
        foreach ($xml->xpath('//main:si') ?: [] as $item) {
            $items[] = self::extractInlineString($item, $mainNamespace);
        }

        return $items;
    }

    private static function resolveFirstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $workbookRelsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = self::loadXml($workbookXml);
        $rels = self::loadXml($workbookRelsXml);

        $workbookNamespaces = $workbook->getNamespaces(true);
        $mainNamespace = $workbookNamespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $relationNamespace = $workbookNamespaces['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        $workbook->registerXPathNamespace('main', $mainNamespace);
        $workbook->registerXPathNamespace('r', $relationNamespace);

        $firstSheet = ($workbook->xpath('//main:sheets/main:sheet[1]') ?: [])[0] ?? null;
        if (!$firstSheet instanceof SimpleXMLElement) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationId = (string) $firstSheet->attributes($relationNamespace)['id'];
        if ($relationId === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        $relsNamespaces = $rels->getNamespaces(true);
        $packageNamespace = $relsNamespaces[''] ?? 'http://schemas.openxmlformats.org/package/2006/relationships';
        $rels->registerXPathNamespace('rel', $packageNamespace);

        foreach ($rels->xpath('//rel:Relationship') ?: [] as $relationship) {
            if ((string) $relationship['Id'] !== $relationId) {
                continue;
            }

            $target = ltrim(str_replace('\\', '/', (string) $relationship['Target']), '/');

            return str_starts_with($target, 'xl/')
                ? $target
                : 'xl/' . $target;
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function parseSheetRows(string $sheetXml, array $sharedStrings): array
    {
        $xml = self::loadXml($sheetXml);
        $namespaces = $xml->getNamespaces(true);
        $mainNamespace = $namespaces[''] ?? 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $xml->registerXPathNamespace('main', $mainNamespace);

        $rows = [];
        $maxColumnIndex = 0;

        foreach ($xml->xpath('//main:sheetData/main:row') ?: [] as $rowNode) {
            $cells = [];
            $rowNode->registerXPathNamespace('main', $mainNamespace);

            foreach ($rowNode->xpath('./main:c') ?: [] as $cellNode) {
                $reference = (string) $cellNode['r'];
                $columnLetters = preg_replace('/[^A-Z]/i', '', strtoupper($reference));
                if ($columnLetters === '') {
                    continue;
                }

                $columnIndex = self::columnLettersToIndex($columnLetters);
                $cells[$columnIndex] = self::cellValue($cellNode, $mainNamespace, $sharedStrings);
                $maxColumnIndex = max($maxColumnIndex, $columnIndex);
            }

            if ($cells === []) {
                continue;
            }

            $rows[] = $cells;
        }

        return array_map(static function (array $row) use ($maxColumnIndex) {
            $normalized = [];

            for ($index = 1; $index <= $maxColumnIndex; $index++) {
                $normalized[] = $row[$index] ?? '';
            }

            return $normalized;
        }, $rows);
    }

    private static function cellValue(SimpleXMLElement $cellNode, string $namespace, array $sharedStrings): string
    {
        $type = (string) $cellNode['t'];

        if ($type === 'inlineStr') {
            $inlineString = $cellNode->children($namespace)->is;

            return trim(self::extractInlineString($inlineString, $namespace));
        }

        if ($type === 's') {
            $sharedIndex = (int) ($cellNode->children($namespace)->v ?? 0);

            return trim((string) ($sharedStrings[$sharedIndex] ?? ''));
        }

        if ($type === 'str') {
            return trim((string) ($cellNode->children($namespace)->v ?? ''));
        }

        if ($type === 'b') {
            return ((string) ($cellNode->children($namespace)->v ?? '0')) === '1' ? '1' : '0';
        }

        return trim((string) ($cellNode->children($namespace)->v ?? ''));
    }

    private static function extractInlineString(?SimpleXMLElement $node, ?string $namespace): string
    {
        if (!$node instanceof SimpleXMLElement) {
            return '';
        }

        $children = $namespace ? $node->children($namespace) : $node->children();
        if (isset($children->t)) {
            return (string) $children->t;
        }

        $result = '';
        foreach ($children->r ?? [] as $run) {
            $runChildren = $namespace ? $run->children($namespace) : $run->children();
            $result .= (string) ($runChildren->t ?? '');
        }

        return $result;
    }

    private static function columnLettersToIndex(string $letters): int
    {
        $value = 0;

        foreach (str_split($letters) as $letter) {
            $value = ($value * 26) + (ord($letter) - 64);
        }

        return $value;
    }

    private static function columnIndexToLetters(int $index): string
    {
        $letters = '';

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letters = chr(65 + $remainder) . $letters;
            $index = intdiv($index - 1, 26);
        }

        return $letters;
    }

    private static function worksheetXml(array $rows): string
    {
        $rowXml = [];

        foreach (array_values($rows) as $rowIndex => $row) {
            $cellXml = [];

            foreach (array_values($row) as $columnIndex => $value) {
                $reference = self::columnIndexToLetters($columnIndex + 1) . ($rowIndex + 1);
                $escapedValue = self::escapeXml((string) $value);
                $cellXml[] = '<c r="' . $reference . '" t="inlineStr"><is><t xml:space="preserve">' . $escapedValue . '</t></is></c>';
            }

            $rowXml[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cellXml) . '</row>';
        }

        return implode('', [
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">',
            '<sheetViews><sheetView workbookViewId="0"/></sheetViews>',
            '<sheetFormatPr defaultRowHeight="15"/>',
            '<sheetData>',
            implode('', $rowXml),
            '</sheetData>',
            '</worksheet>',
        ]);
    }

    private static function contentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML;
    }

    private static function rootRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;
    }

    private static function workbookXml(string $sheetTitle): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $sheetTitle . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private static function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
    <fills count="1"><fill><patternFill patternType="none"/></fill></fills>
    <borders count="1"><border/></borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML;
    }

    private static function appPropertiesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
    <Application>Провериум</Application>
</Properties>
XML;
    }

    private static function corePropertiesXml(string $timestamp): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>Провериум</dc:creator>'
            . '<cp:lastModifiedBy>Провериум</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $timestamp . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private static function loadXml(string $contents): SimpleXMLElement
    {
        $xml = simplexml_load_string($contents);
        if (!$xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Не удалось разобрать XML внутри XLSX-файла.');
        }

        return $xml;
    }

    private static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
