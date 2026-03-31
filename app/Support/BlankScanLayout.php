<?php

namespace App\Support;

class BlankScanLayout
{
    public const PAGE_WIDTH_MM = 210.0;
    public const PAGE_HEIGHT_MM = 297.0;
    public const MARKER_SIZE_MM = 8.0;
    public const MARKER_OFFSET_MM = 9.0;

    public const CODE_TOP_MM = 267.6;
    public const CODE_GRID_COLUMNS = 14;
    public const CODE_GRID_ROWS = 4;
    public const CODE_CELL_WIDTH_MM = 2.6;
    public const CODE_CELL_HEIGHT_MM = 2.6;
    public const CODE_CELL_GAP_MM = 0.6;
    public const CODE_BITS = 56;

    public const TABLE_TOP_MM = 66.5;
    public const TABLE_HEADER_HEIGHT_MM = 6.5;
    public const GRID_TOP_MM = 73.0;
    public const GRID_BOTTOM_MM = 262.5;
    public const GRID_LEFT_MM = 14.0;
    public const GRID_RIGHT_MM = 196.0;
    public const GRID_ROWS_PER_PAGE = 24;

    public const QUESTION_NUMBER_WIDTH_MM = 8.0;
    public const QUESTION_TYPE_WIDTH_MM = 8.0;
    public const ANSWER_FIELD_RIGHT_PADDING_MM = 1.4;
    public const ANSWER_OPTION_SIZE_MM = 3.2;
    public const ANSWER_OPTION_GAP_MM = 1.1;
    public const ANSWER_OPTION_COUNT = 5;
    public const ANSWER_CELL_GUIDE_RATIO = 0.82;
    public const ANSWER_SCAN_WINDOW_RATIO = 0.50;
    public const ANSWER_CORE_WINDOW_RATIO = 0.30;
    public const ANSWER_WINDOW_OFFSET_X_MM = 0.0;
    public const ANSWER_WINDOW_OFFSET_Y_MM = 0.0;
    public const CODE_MASK = 0xA5;

    public static function maxQuestions(): int
    {
        return PHP_INT_MAX;
    }

    public static function questionsPerPage(): int
    {
        return self::GRID_ROWS_PER_PAGE;
    }

    public static function questionPageCount(int $questionCount): int
    {
        return max(1, (int) ceil(max(1, $questionCount) / self::GRID_ROWS_PER_PAGE));
    }

    public static function questionStartIndexForPage(int $pageNumber): int
    {
        return max(0, ($pageNumber - 1) * self::GRID_ROWS_PER_PAGE);
    }

    public static function questionCountForPage(int $questionCount, int $pageNumber): int
    {
        $start = self::questionStartIndexForPage($pageNumber);

        return max(0, min(self::GRID_ROWS_PER_PAGE, $questionCount - $start));
    }

    public static function questionRowHeightMm(): float
    {
        return (self::GRID_BOTTOM_MM - self::GRID_TOP_MM) / self::GRID_ROWS_PER_PAGE;
    }

    public static function questionColumnCount(int $questionCount): int
    {
        return 1;
    }

    public static function questionColumnWidthMm(int $questionCount = 0): float
    {
        return self::GRID_RIGHT_MM - self::GRID_LEFT_MM;
    }

    public static function questionRowTopMm(int $rowIndex): float
    {
        return self::GRID_TOP_MM + ($rowIndex * self::questionRowHeightMm());
    }

    public static function questionColumnLeftMm(int $questionCount = 0, int $index = 0): float
    {
        return self::GRID_LEFT_MM;
    }

    public static function answerFieldWidthMm(): float
    {
        return (self::ANSWER_OPTION_SIZE_MM * self::ANSWER_OPTION_COUNT)
            + (self::ANSWER_OPTION_GAP_MM * (self::ANSWER_OPTION_COUNT - 1));
    }

    public static function answerFieldLeftOffsetMm(int $questionCount = 0): float
    {
        return self::questionColumnWidthMm($questionCount)
            - self::answerFieldWidthMm()
            - self::ANSWER_FIELD_RIGHT_PADDING_MM;
    }

    public static function questionTextWidthMm(int $questionCount = 0): float
    {
        return max(
            10.0,
            self::answerFieldLeftOffsetMm($questionCount)
                - self::QUESTION_NUMBER_WIDTH_MM
                - self::QUESTION_TYPE_WIDTH_MM
        );
    }

    public static function answerCellMm(int $questionCount, int $questionIndex, int $optionIndex): array
    {
        $rowIndex = $questionIndex % self::GRID_ROWS_PER_PAGE;
        $left = self::questionColumnLeftMm($questionCount, $questionIndex)
            + self::answerFieldLeftOffsetMm($questionCount)
            + ($optionIndex * (self::ANSWER_OPTION_SIZE_MM + self::ANSWER_OPTION_GAP_MM));

        $top = self::questionRowTopMm($rowIndex)
            + ((self::questionRowHeightMm() - self::ANSWER_OPTION_SIZE_MM) / 2);

        return [
            'left' => $left,
            'top' => $top,
            'size' => self::ANSWER_OPTION_SIZE_MM,
        ];
    }

    public static function answerScanWindowMm(int $questionCount, int $questionIndex, int $optionIndex): array
    {
        $cell = self::answerCellMm($questionCount, $questionIndex, $optionIndex);
        $windowSize = self::ANSWER_OPTION_SIZE_MM * self::ANSWER_SCAN_WINDOW_RATIO;
        $centeredInset = (self::ANSWER_OPTION_SIZE_MM - $windowSize) / 2;

        return [
            'left' => $cell['left'] + $centeredInset + self::ANSWER_WINDOW_OFFSET_X_MM,
            'top' => $cell['top'] + $centeredInset + self::ANSWER_WINDOW_OFFSET_Y_MM,
            'size' => $windowSize,
        ];
    }

    public static function answerCellGuideMm(int $questionCount, int $questionIndex, int $optionIndex): array
    {
        $cell = self::answerCellMm($questionCount, $questionIndex, $optionIndex);
        $guideSize = self::ANSWER_OPTION_SIZE_MM * self::ANSWER_CELL_GUIDE_RATIO;
        $centeredInset = (self::ANSWER_OPTION_SIZE_MM - $guideSize) / 2;

        return [
            'left' => $cell['left'] + $centeredInset + self::ANSWER_WINDOW_OFFSET_X_MM,
            'top' => $cell['top'] + $centeredInset + self::ANSWER_WINDOW_OFFSET_Y_MM,
            'size' => $guideSize,
        ];
    }

    public static function markerCentersMm(): array
    {
        $offset = self::MARKER_OFFSET_MM + (self::MARKER_SIZE_MM / 2);

        return [
            'tl' => ['x' => $offset, 'y' => $offset],
            'tr' => ['x' => self::PAGE_WIDTH_MM - $offset, 'y' => $offset],
            'bl' => ['x' => $offset, 'y' => self::PAGE_HEIGHT_MM - $offset],
            'br' => ['x' => self::PAGE_WIDTH_MM - $offset, 'y' => self::PAGE_HEIGHT_MM - $offset],
        ];
    }

    public static function bitStringFor(int $blankFormId): string
    {
        return self::bitStringForPage($blankFormId, 1, 1);
    }

    public static function bitStringForPage(int $blankFormId, int $pageNumber, int $pageCount): string
    {
        $normalizedPageNumber = max(1, min(4095, $pageNumber));
        $normalizedPageCount = max($normalizedPageNumber, min(4095, $pageCount));
        $checksum = (($blankFormId ^ $normalizedPageNumber ^ $normalizedPageCount ^ self::CODE_MASK) & 0xFF);

        return str_pad(decbin($blankFormId), 24, '0', STR_PAD_LEFT)
            . str_pad(decbin($normalizedPageNumber), 12, '0', STR_PAD_LEFT)
            . str_pad(decbin($normalizedPageCount), 12, '0', STR_PAD_LEFT)
            . str_pad(decbin($checksum), 8, '0', STR_PAD_LEFT);
    }

    public static function decodeBitString(string $bitString): ?int
    {
        return self::decodePageBitString($bitString)['blank_form_id'] ?? null;
    }

    public static function decodePageBitString(string $bitString): ?array
    {
        if (strlen($bitString) !== self::CODE_BITS) {
            return null;
        }

        $blankFormId = bindec(substr($bitString, 0, 24));
        $pageNumber = bindec(substr($bitString, 24, 12));
        $pageCount = bindec(substr($bitString, 36, 12));
        $checksum = bindec(substr($bitString, 48, 8));

        if ((($blankFormId ^ $pageNumber ^ $pageCount ^ self::CODE_MASK) & 0xFF) !== $checksum) {
            return null;
        }

        if ($pageNumber < 1 || $pageCount < 1 || $pageNumber > $pageCount) {
            return null;
        }

        return [
            'blank_form_id' => $blankFormId,
            'page_number' => $pageNumber,
            'page_count' => $pageCount,
        ];
    }

    public static function answerLetters(): array
    {
        return ['A', 'B', 'C', 'D', 'E'];
    }

    public static function codeGridWidthMm(): float
    {
        return (self::CODE_GRID_COLUMNS * self::CODE_CELL_WIDTH_MM)
            + ((self::CODE_GRID_COLUMNS - 1) * self::CODE_CELL_GAP_MM);
    }

    public static function codeGridHeightMm(): float
    {
        return (self::CODE_GRID_ROWS * self::CODE_CELL_HEIGHT_MM)
            + ((self::CODE_GRID_ROWS - 1) * self::CODE_CELL_GAP_MM);
    }

    public static function codeLeftMm(): float
    {
        return (self::PAGE_WIDTH_MM - self::codeGridWidthMm()) / 2;
    }

    public static function codeCellMm(int $index): array
    {
        $row = intdiv($index, self::CODE_GRID_COLUMNS);
        $column = $index % self::CODE_GRID_COLUMNS;

        return [
            'left' => self::codeLeftMm() + ($column * (self::CODE_CELL_WIDTH_MM + self::CODE_CELL_GAP_MM)),
            'top' => self::CODE_TOP_MM + ($row * (self::CODE_CELL_HEIGHT_MM + self::CODE_CELL_GAP_MM)),
            'width' => self::CODE_CELL_WIDTH_MM,
            'height' => self::CODE_CELL_HEIGHT_MM,
        ];
    }
}
