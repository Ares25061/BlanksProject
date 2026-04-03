<?php

namespace App\Support;

class UnifiedSheetLayout
{
    public const VERSION = 'unified-sheet-v2';

    public const PAGE_WIDTH_MM = 210.0;
    public const PAGE_HEIGHT_MM = 297.0;

    public const PAGE_MARGIN_MM = 12.0;
    public const COLUMN_COUNT = 2;
    public const COLUMN_GAP_MM = 6.0;

    public const SERVICE_ZONE_HEIGHT_MM = 40.0;
    public const SERVICE_ZONE_INSET_MM = 2.0;
    public const FOOTER_HEIGHT_MM = 8.0;
    public const QR_SIZE_MM = 30.0;
    public const QR_TOP_OFFSET_MM = 3.0;

    public const MARKER_SIZE_MM = 7.0;
    public const MARKER_EDGE_OFFSET_MM = 6.0;

    public const QUESTION_GAP_MM = 5.0;
    public const QUESTION_INNER_PADDING_MM = 3.5;
    public const QUESTION_BORDER_RADIUS_MM = 3.0;

    public const CHOICE_BOX_SIZE_MM = 5.5;
    public const CHOICE_CELL_GAP_MM = 4.0;
    public const CHOICE_CELL_LABEL_GAP_MM = 4.25;
    public const CHOICE_ROW_GAP_MM = 2.8;
    public const CHOICE_MAX_PER_ROW = 4;

    public const ANSWER_LABEL_WIDTH_MM = 18.0;
    public const ANSWER_GAP_MM = 2.4;

    public const TITLE_CHAR_WIDTH_MM = 1.52;
    public const TITLE_LINE_HEIGHT_MM = 4.25;
    public const OPTION_CHAR_WIDTH_MM = 1.72;
    public const OPTION_LINE_HEIGHT_MM = 3.7;
    public const LABEL_LINE_HEIGHT_MM = 4.0;
    public const LABEL_TOP_GAP_MM = 1.8;
    public const TITLE_TO_OPTIONS_GAP_MM = 1.0;
    public const OPTIONS_TO_LABEL_GAP_MM = 1.8;
    public const BOTTOM_BUFFER_MM = 1.6;

    public static function columnWidthMm(): float
    {
        return (self::PAGE_WIDTH_MM - (self::PAGE_MARGIN_MM * 2) - (self::COLUMN_GAP_MM * (self::COLUMN_COUNT - 1)))
            / self::COLUMN_COUNT;
    }

    public static function questionAreaTopMm(): float
    {
        return self::PAGE_MARGIN_MM + self::SERVICE_ZONE_HEIGHT_MM;
    }

    public static function questionAreaBottomMm(): float
    {
        return self::PAGE_HEIGHT_MM - self::PAGE_MARGIN_MM - self::FOOTER_HEIGHT_MM;
    }

    public static function columnLeftMm(int $columnIndex): float
    {
        return self::PAGE_MARGIN_MM + ($columnIndex * (self::columnWidthMm() + self::COLUMN_GAP_MM));
    }

    public static function markerRectsMm(): array
    {
        $size = self::MARKER_SIZE_MM;
        $offset = self::MARKER_EDGE_OFFSET_MM;

        return [
            'top_left' => [
                'left_mm' => $offset,
                'top_mm' => $offset,
                'width_mm' => $size,
                'height_mm' => $size,
            ],
            'top_right' => [
                'left_mm' => self::PAGE_WIDTH_MM - $offset - $size,
                'top_mm' => $offset,
                'width_mm' => $size,
                'height_mm' => $size,
            ],
            'bottom_left' => [
                'left_mm' => $offset,
                'top_mm' => self::PAGE_HEIGHT_MM - $offset - $size,
                'width_mm' => $size,
                'height_mm' => $size,
            ],
            'bottom_right' => [
                'left_mm' => self::PAGE_WIDTH_MM - $offset - $size,
                'top_mm' => self::PAGE_HEIGHT_MM - $offset - $size,
                'width_mm' => $size,
                'height_mm' => $size,
            ],
        ];
    }

    public static function markerCentersMm(): array
    {
        return collect(self::markerRectsMm())
            ->map(fn (array $rect) => [
                'x_mm' => $rect['left_mm'] + ($rect['width_mm'] / 2),
                'y_mm' => $rect['top_mm'] + ($rect['height_mm'] / 2),
            ])
            ->all();
    }

    public static function serviceZoneMm(): array
    {
        return [
            'left_mm' => self::PAGE_MARGIN_MM,
            'top_mm' => self::PAGE_MARGIN_MM,
            'width_mm' => self::PAGE_WIDTH_MM - (self::PAGE_MARGIN_MM * 2),
            'height_mm' => self::SERVICE_ZONE_HEIGHT_MM - self::SERVICE_ZONE_INSET_MM,
        ];
    }

    public static function qrZoneMm(): array
    {
        return [
            'left_mm' => self::PAGE_WIDTH_MM - self::PAGE_MARGIN_MM - self::QR_SIZE_MM,
            'top_mm' => self::PAGE_MARGIN_MM + self::QR_TOP_OFFSET_MM,
            'width_mm' => self::QR_SIZE_MM,
            'height_mm' => self::QR_SIZE_MM,
        ];
    }

    public static function answerLetters(): array
    {
        return range('A', 'Z');
    }
}
