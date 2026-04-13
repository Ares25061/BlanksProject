<?php

namespace Tests\Unit;

use App\Services\BlankSheetQrCodeService;
use App\Support\UnifiedSheetLayout;
use PHPUnit\Framework\TestCase;

class UnifiedSheetLayoutTest extends TestCase
{
    public function test_layout_matches_ocr_upd_page_geometry(): void
    {
        $this->assertSame(90.0, UnifiedSheetLayout::columnWidthMm());
        $this->assertSame(31.0, UnifiedSheetLayout::questionAreaTopMm());
        $this->assertSame(281.0, UnifiedSheetLayout::questionAreaBottomMm());
        $this->assertSame([
            'x_mm' => 7.0,
            'y_mm' => 7.0,
        ], UnifiedSheetLayout::markerCentersMm()['top_left']);

        $serviceZone = UnifiedSheetLayout::serviceZoneMm();
        $qrZone = UnifiedSheetLayout::qrZoneMm();

        $this->assertGreaterThan(
            $serviceZone['left_mm'] + $serviceZone['width_mm'],
            $qrZone['left_mm']
        );
    }

    public function test_qr_payload_round_trip_preserves_blank_form_and_page_data(): void
    {
        $service = new BlankSheetQrCodeService();
        $payload = $service->buildPayload([
            'blank_form_id' => 91234,
            'form_number' => 'TEST-91234',
            'page_number' => 2,
            'page_count' => 5,
        ]);

        $this->assertSame([
            'blank_form_id' => 91234,
            'page_number' => 2,
            'page_count' => 5,
            'form_number' => 'TEST-91234',
        ], $service->normalizePayload($payload));
    }
}
