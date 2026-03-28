<?php

namespace App\Support;

class BlankScanLayout
{
    public const PAGE_WIDTH_MM = 210.0;
    public const PAGE_HEIGHT_MM = 297.0;
    public const MARKER_SIZE_MM = 8.0;
    public const MARKER_OFFSET_MM = 9.0;
    public const CODE_TOP_MM = 273.8;
    public const CODE_LEFT_MM = 79.7;
    public const CODE_GRID_COLUMNS = 16;
    public const CODE_GRID_ROWS = 2;
    public const CODE_CELL_WIDTH_MM = 2.6;
    public const CODE_CELL_HEIGHT_MM = 2.6;
    public const CODE_CELL_GAP_MM = 0.6;
    public const CODE_BITS = 32;
    public const TABLE_TOP_MM = 108.0;
    public const TABLE_HEADER_HEIGHT_MM = 7.5;
    public const GRID_TOP_MM = 115.5;
    public const GRID_BOTTOM_MM = 251.5;
    public const GRID_LEFT_MM = 14.0;
    public const GRID_RIGHT_MM = 196.0;
    public const GRID_ROWS_PER_COLUMN = 15;
    public const GRID_MAX_COLUMNS = 1;
    public const GRID_COLUMN_GAP_MM = 4.0;
    public const QUESTION_NUMBER_WIDTH_MM = 8.0;
    public const QUESTION_TYPE_WIDTH_MM = 8.0;
    public const ANSWER_FIELD_RIGHT_PADDING_MM = 1.4;
    public const ANSWER_OPTION_SIZE_MM = 3.2;
    public const ANSWER_OPTION_GAP_MM = 1.1;
    public const ANSWER_OPTION_COUNT = 5;
    public const CODE_MASK = 0xA5;

    public static function maxQuestions(): int
    {
        return self::GRID_ROWS_PER_COLUMN * self::GRID_MAX_COLUMNS;
    }

    public static function questionColumnCount(int $questionCount): int
    {
        return max(1, min(self::GRID_MAX_COLUMNS, (int) ceil($questionCount / self::GRID_ROWS_PER_COLUMN)));
    }

    public static function questionRowHeightMm(): float
    {
        return (self::GRID_BOTTOM_MM - self::GRID_TOP_MM) / self::GRID_ROWS_PER_COLUMN;
    }

    public static function questionColumnWidthMm(int $questionCount): float
    {
        $columnCount = self::questionColumnCount($questionCount);
        $totalGap = ($columnCount - 1) * self::GRID_COLUMN_GAP_MM;

        return (self::GRID_RIGHT_MM - self::GRID_LEFT_MM - $totalGap) / $columnCount;
    }

    public static function questionRowTopMm(int $index): float
    {
        $row = $index % self::GRID_ROWS_PER_COLUMN;

        return self::GRID_TOP_MM + ($row * self::questionRowHeightMm());
    }

    public static function questionColumnLeftMm(int $questionCount, int $index): float
    {
        $column = intdiv($index, self::GRID_ROWS_PER_COLUMN);

        return self::GRID_LEFT_MM + ($column * (self::questionColumnWidthMm($questionCount) + self::GRID_COLUMN_GAP_MM));
    }

    public static function answerFieldWidthMm(): float
    {
        return (self::ANSWER_OPTION_SIZE_MM * self::ANSWER_OPTION_COUNT)
            + (self::ANSWER_OPTION_GAP_MM * (self::ANSWER_OPTION_COUNT - 1));
    }

    public static function answerFieldLeftOffsetMm(int $questionCount): float
    {
        return self::questionColumnWidthMm($questionCount)
            - self::answerFieldWidthMm()
            - self::ANSWER_FIELD_RIGHT_PADDING_MM;
    }

    public static function questionTextWidthMm(int $questionCount): float
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
        $left = self::questionColumnLeftMm($questionCount, $questionIndex)
            + self::answerFieldLeftOffsetMm($questionCount)
            + ($optionIndex * (self::ANSWER_OPTION_SIZE_MM + self::ANSWER_OPTION_GAP_MM));

        $top = self::questionRowTopMm($questionIndex)
            + ((self::questionRowHeightMm() - self::ANSWER_OPTION_SIZE_MM) / 2);

        return [
            'left' => $left,
            'top' => $top,
            'size' => self::ANSWER_OPTION_SIZE_MM,
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
        $checksum = ($blankFormId ^ self::CODE_MASK) & 0xFF;

        return str_pad(decbin($blankFormId), 24, '0', STR_PAD_LEFT)
            . str_pad(decbin($checksum), 8, '0', STR_PAD_LEFT);
    }

    public static function decodeBitString(string $bitString): ?int
    {
        if (strlen($bitString) !== self::CODE_BITS) {
            return null;
        }

        $blankFormId = bindec(substr($bitString, 0, 24));
        $checksum = bindec(substr($bitString, 24, 8));

        if ((($blankFormId ^ self::CODE_MASK) & 0xFF) !== $checksum) {
            return null;
        }

        return $blankFormId;
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

    public static function codeCellMm(int $index): array
    {
        $row = intdiv($index, self::CODE_GRID_COLUMNS);
        $column = $index % self::CODE_GRID_COLUMNS;

        return [
            'left' => self::CODE_LEFT_MM + ($column * (self::CODE_CELL_WIDTH_MM + self::CODE_CELL_GAP_MM)),
            'top' => self::CODE_TOP_MM + ($row * (self::CODE_CELL_HEIGHT_MM + self::CODE_CELL_GAP_MM)),
            'width' => self::CODE_CELL_WIDTH_MM,
            'height' => self::CODE_CELL_HEIGHT_MM,
        ];
    }
}
