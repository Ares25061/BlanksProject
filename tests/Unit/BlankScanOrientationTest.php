<?php

namespace Tests\Unit;

use App\Services\BlankScanService;
use App\Support\BlankScanLayout;
use PHPUnit\Framework\TestCase;

class BlankScanOrientationTest extends TestCase
{
    public function test_resolve_scan_frame_rotates_upside_down_portrait_page_until_code_becomes_readable(): void
    {
        $image = $this->createCornerMarkedImage(20, 30, 19, 29);
        $service = new class extends BlankScanService {
            public array $topLeftColors = [];

            public function __construct()
            {
            }

            public function resolvePublic($image): array
            {
                return $this->resolveScanFrame($image);
            }

            protected function tryResolveScanFrame($image): ?array
            {
                $topLeftColor = \imagecolorat($image, 0, 0) & 0xFFFFFF;
                $this->topLeftColors[] = $topLeftColor;

                if ($topLeftColor !== 0xFF0000) {
                    return null;
                }

                return [
                    'markers' => [],
                    'bit_string' => str_repeat('0', BlankScanLayout::CODE_BITS),
                    'projection_calibration' => null,
                    'page_payload' => [
                        'blank_form_id' => 1,
                        'page_number' => 1,
                        'page_count' => 1,
                    ],
                ];
            }
        };

        $frame = $service->resolvePublic($image);
        $oriented = $frame['image'];

        $this->assertSame([0xFFFFFF, 0xFF0000], $service->topLeftColors);
        $this->assertSame(0xFF0000, \imagecolorat($oriented, 0, 0) & 0xFFFFFF);
        $this->assertSame(20, \imagesx($oriented));
        $this->assertSame(30, \imagesy($oriented));

        \imagedestroy($oriented);
    }

    public function test_resolve_scan_frame_rotates_landscape_page_into_readable_portrait_orientation(): void
    {
        $image = $this->createCornerMarkedImage(30, 20, 29, 0);
        $service = new class extends BlankScanService {
            public array $topLeftColors = [];

            public function __construct()
            {
            }

            public function resolvePublic($image): array
            {
                return $this->resolveScanFrame($image);
            }

            protected function tryResolveScanFrame($image): ?array
            {
                $topLeftColor = \imagecolorat($image, 0, 0) & 0xFFFFFF;
                $this->topLeftColors[] = $topLeftColor;

                if ($topLeftColor !== 0xFF0000) {
                    return null;
                }

                return [
                    'markers' => [],
                    'bit_string' => str_repeat('0', BlankScanLayout::CODE_BITS),
                    'projection_calibration' => null,
                    'page_payload' => [
                        'blank_form_id' => 1,
                        'page_number' => 1,
                        'page_count' => 1,
                    ],
                ];
            }
        };

        $frame = $service->resolvePublic($image);
        $oriented = $frame['image'];

        $this->assertSame([0xFF0000], $service->topLeftColors);
        $this->assertSame(0xFF0000, \imagecolorat($oriented, 0, 0) & 0xFFFFFF);
        $this->assertSame(20, \imagesx($oriented));
        $this->assertSame(30, \imagesy($oriented));

        \imagedestroy($oriented);
    }

    private function createCornerMarkedImage(int $width, int $height, int $markX, int $markY)
    {
        $image = \imagecreatetruecolor($width, $height);
        \imagefill($image, 0, 0, 0xFFFFFF);
        \imagesetpixel($image, $markX, $markY, 0xFF0000);

        return $image;
    }
}
