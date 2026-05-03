<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class SimpleXlsx
{
    public static function readRows(string $path): array
    {
        if (class_exists(ZipArchive::class)) {
            try {
                return self::readRowsWithZipArchive($path);
            } catch (RuntimeException) {
                // Fall through to the bundled ZIP reader below.
            }
        }

        return self::readRowsWithoutZipArchive($path);
    }

    private static function readRowsWithZipArchive(string $path): array
    {
        $zip = new ZipArchive;
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

    private static function readRowsWithoutZipArchive(string $path): array
    {
        $entries = self::readZipEntries($path);
        $sharedStrings = self::readSharedStringsXml($entries['xl/sharedStrings.xml'] ?? null);
        $sheetPath = self::resolveFirstWorksheetPathFromXml(
            $entries['xl/workbook.xml'] ?? null,
            $entries['xl/_rels/workbook.xml.rels'] ?? null
        );
        $sheetXml = $entries[$sheetPath] ?? null;

        if (! is_string($sheetXml)) {
            throw new RuntimeException('Не удалось прочитать первый лист XLSX-файла.');
        }

        return self::parseSheetRows($sheetXml, $sharedStrings);
    }

    public static function writeWorkbook(string $sheetName, array $rows): string
    {
        $directory = storage_path('framework/testing/xlsx-temp');
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException('Не удалось подготовить папку для временных XLSX-файлов.');
        }

        $tempPath = $directory.DIRECTORY_SEPARATOR.uniqid('blanks_xlsx_', true).'.xlsx';
        $timestamp = now()->toIso8601String();
        $sheetTitle = self::escapeXml(mb_substr(trim($sheetName) ?: 'Sheet1', 0, 31));
        $entries = self::workbookEntries($timestamp, $sheetTitle, $rows);

        if (class_exists(ZipArchive::class)) {
            try {
                self::writeWithZipArchive($tempPath, $entries);

                return $tempPath;
            } catch (RuntimeException) {
                @unlink($tempPath);
            }
        }

        self::writeStoredZip($tempPath, $entries);

        return $tempPath;
    }

    private static function writeWithZipArchive(string $path, array $entries): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new RuntimeException('Не удалось собрать XLSX-файл.');
        }

        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        if ($zip->close() !== true) {
            @unlink($path);
            throw new RuntimeException('Не удалось собрать XLSX-файл.');
        }
    }

    private static function writeStoredZip(string $path, array $entries): void
    {
        $localParts = [];
        $centralParts = [];
        $offset = 0;
        [$dosTime, $dosDate] = self::zipDosDateTime();

        foreach ($entries as $name => $contents) {
            $name = str_replace('\\', '/', (string) $name);
            $contents = (string) $contents;
            $crc = (int) hexdec(hash('crc32b', $contents));
            $size = strlen($contents);
            $nameLength = strlen($name);

            $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0).$name;
            $centralHeader = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset).$name;

            $localParts[] = $localHeader.$contents;
            $centralParts[] = $centralHeader;
            $offset += strlen($localHeader) + $size;
        }

        $centralDirectory = implode('', $centralParts);
        $endOfCentralDirectory = pack('VvvvvVVv', 0x06054b50, 0, 0, count($entries), count($entries), strlen($centralDirectory), $offset, 0);
        $contents = implode('', $localParts).$centralDirectory.$endOfCentralDirectory;

        if (file_put_contents($path, $contents) === false) {
            @unlink($path);
            throw new RuntimeException('Не удалось собрать XLSX-файл.');
        }
    }

    private static function workbookEntries(string $timestamp, string $sheetTitle, array $rows): array
    {
        return [
            '[Content_Types].xml' => self::contentTypesXml(),
            '_rels/.rels' => self::rootRelationshipsXml(),
            'docProps/app.xml' => self::appPropertiesXml(),
            'docProps/core.xml' => self::corePropertiesXml($timestamp),
            'xl/workbook.xml' => self::workbookXml($sheetTitle),
            'xl/_rels/workbook.xml.rels' => self::workbookRelationshipsXml(),
            'xl/styles.xml' => self::stylesXml(),
            'xl/worksheets/sheet1.xml' => self::worksheetXml($rows),
        ];
    }

    private static function zipDosDateTime(): array
    {
        $year = max(1980, (int) date('Y'));
        $date = (($year - 1980) << 9) | ((int) date('n') << 5) | (int) date('j');
        $time = ((int) date('G') << 11) | ((int) date('i') << 5) | intdiv((int) date('s'), 2);

        return [$time, $date];
    }

    private static function ensureZipSupport(): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('На сервере недоступна поддержка ZIP, поэтому XLSX сейчас недоступен.');
        }
    }

    private static function readSharedStrings(ZipArchive $zip): array
    {
        return self::readSharedStringsXml($zip->getFromName('xl/sharedStrings.xml') ?: null);
    }

    private static function readSharedStringsXml(?string $sharedStringsXml): array
    {
        if ($sharedStringsXml === null) {
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
        return self::resolveFirstWorksheetPathFromXml(
            $zip->getFromName('xl/workbook.xml') ?: null,
            $zip->getFromName('xl/_rels/workbook.xml.rels') ?: null
        );
    }

    private static function resolveFirstWorksheetPathFromXml(?string $workbookXml, ?string $workbookRelsXml): string
    {
        if ($workbookXml === null || $workbookRelsXml === null) {
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
        if (! $firstSheet instanceof SimpleXMLElement) {
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
                : 'xl/'.$target;
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function readZipEntries(string $path): array
    {
        $contents = file_get_contents($path);
        if (! is_string($contents)) {
            throw new RuntimeException('Не удалось открыть XLSX-файл.');
        }

        $endOffset = self::findEndOfCentralDirectory($contents);
        if ($endOffset === null) {
            throw new RuntimeException('Не удалось открыть XLSX-файл.');
        }

        $end = unpack('vdisk/vcentralDisk/ventriesDisk/ventries/VcentralSize/VcentralOffset/vcommentLength', substr($contents, $endOffset + 4, 18));
        if (! is_array($end)) {
            throw new RuntimeException('Не удалось открыть XLSX-файл.');
        }

        $entries = [];
        $offset = (int) $end['centralOffset'];

        for ($index = 0; $index < (int) $end['entries']; $index++) {
            if (substr($contents, $offset, 4) !== "PK\x01\x02") {
                throw new RuntimeException('Не удалось открыть XLSX-файл.');
            }

            $header = unpack(
                'vversionMade/vversionNeeded/vflags/vmethod/vtime/vdate/Vcrc/VcompressedSize/VuncompressedSize/vnameLength/vextraLength/vcommentLength/vdiskStart/vinternalAttributes/VexternalAttributes/VlocalOffset',
                substr($contents, $offset + 4, 42)
            );

            if (! is_array($header)) {
                throw new RuntimeException('Не удалось открыть XLSX-файл.');
            }

            $nameLength = (int) $header['nameLength'];
            $extraLength = (int) $header['extraLength'];
            $commentLength = (int) $header['commentLength'];
            $name = str_replace('\\', '/', substr($contents, $offset + 46, $nameLength));
            $localOffset = (int) $header['localOffset'];

            if (substr($contents, $localOffset, 4) !== "PK\x03\x04") {
                throw new RuntimeException('Не удалось открыть XLSX-файл.');
            }

            $localHeader = unpack('vversionNeeded/vflags/vmethod/vtime/vdate/Vcrc/VcompressedSize/VuncompressedSize/vnameLength/vextraLength', substr($contents, $localOffset + 4, 26));
            if (! is_array($localHeader)) {
                throw new RuntimeException('Не удалось открыть XLSX-файл.');
            }

            $dataOffset = $localOffset + 30 + (int) $localHeader['nameLength'] + (int) $localHeader['extraLength'];
            $compressedData = substr($contents, $dataOffset, (int) $header['compressedSize']);
            $entries[$name] = self::uncompressZipEntry($compressedData, (int) $header['method']);
            $offset += 46 + $nameLength + $extraLength + $commentLength;
        }

        return $entries;
    }

    private static function uncompressZipEntry(string $contents, int $method): string
    {
        if ($method === 0) {
            return $contents;
        }

        if ($method === 8) {
            $inflated = gzinflate($contents);
            if (is_string($inflated)) {
                return $inflated;
            }
        }

        throw new RuntimeException('Не удалось открыть XLSX-файл.');
    }

    private static function findEndOfCentralDirectory(string $contents): ?int
    {
        $minimumOffset = max(0, strlen($contents) - 65557);

        for ($offset = strlen($contents) - 22; $offset >= $minimumOffset; $offset--) {
            if (substr($contents, $offset, 4) === "PK\x05\x06") {
                return $offset;
            }
        }

        return null;
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
        if (! $node instanceof SimpleXMLElement) {
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
            $letters = chr(65 + $remainder).$letters;
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
                $reference = self::columnIndexToLetters($columnIndex + 1).($rowIndex + 1);
                $escapedValue = self::escapeXml((string) $value);
                $cellXml[] = '<c r="'.$reference.'" t="inlineStr"><is><t xml:space="preserve">'.$escapedValue.'</t></is></c>';
            }

            $rowXml[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cellXml).'</row>';
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
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$sheetTitle.'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
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
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>Провериум</dc:creator>'
            .'<cp:lastModifiedBy>Провериум</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private static function loadXml(string $contents): SimpleXMLElement
    {
        $xml = simplexml_load_string($contents);
        if (! $xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Не удалось разобрать XML внутри XLSX-файла.');
        }

        return $xml;
    }

    private static function escapeXml(string $value): string
    {
        $normalized = Utf8Normalizer::string($value) ?? '';
        $normalized = preg_replace(
            '/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u',
            '',
            $normalized
        ) ?? '';

        return htmlspecialchars($normalized, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
    }
}
