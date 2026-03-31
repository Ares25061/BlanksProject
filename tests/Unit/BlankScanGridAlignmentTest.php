<?php

namespace Tests\Unit;

use App\Services\BlankScanService;
use PHPUnit\Framework\TestCase;

class BlankScanGridAlignmentTest extends TestCase
{
    public function test_aligned_column_borders_keep_all_option_widths_uniform(): void
    {
        $service = new class extends BlankScanService {
            public function __construct()
            {
            }

            public function align(array $expectedPositions, array $detectedPositions): array
            {
                return $this->alignDetectedBorderPositions($expectedPositions, $detectedPositions);
            }
        };

        $expected = [100, 119, 126, 145, 152, 171, 178, 197, 204, 223];
        $detected = [99, 118, 125, 145, 151, 171, 177, 198, 204, 228];
        $aligned = $service->align($expected, $detected);
        $widths = $this->pairWidths($aligned);

        $this->assertLessThanOrEqual(1, max($widths) - min($widths));
        $this->assertLessThanOrEqual(1, abs($widths[0] - $widths[4]));
    }

    public function test_aligned_row_borders_keep_row_heights_uniform_without_accumulated_drift(): void
    {
        $service = new class extends BlankScanService {
            public function __construct()
            {
            }

            public function align(array $expectedPositions, array $detectedPositions): array
            {
                return $this->alignDetectedBorderPositions($expectedPositions, $detectedPositions);
            }
        };

        $expected = [200, 220, 247, 267, 294, 314, 341, 361, 388, 408, 435, 455];
        $detected = [201, 221, 248, 269, 296, 317, 344, 366, 392, 415, 441, 465];
        $aligned = $service->align($expected, $detected);
        $heights = $this->pairWidths($aligned);

        $this->assertLessThanOrEqual(1, max($heights) - min($heights));
    }

    private function pairWidths(array $borders): array
    {
        $widths = [];

        for ($index = 0; $index + 1 < count($borders); $index += 2) {
            $widths[] = $borders[$index + 1] - $borders[$index];
        }

        return $widths;
    }
}
