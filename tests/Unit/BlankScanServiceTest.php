<?php

namespace Tests\Unit;

use App\Services\BlankFormService;
use App\Services\BlankScanService;
use App\Services\BlankSheetManifestService;
use App\Services\BlankSheetQrCodeService;
use App\Services\GradingService;
use App\Services\PythonCellOcrService;
use App\Services\ScanPreviewService;
use App\Services\TestVariantService;
use PHPUnit\Framework\TestCase;

class BlankScanServiceTest extends TestCase
{
    public function test_extract_answers_reports_borderline_cells(): void
    {
        $pythonCellOcrService = $this->createMock(PythonCellOcrService::class);
        $pythonCellOcrService->expects($this->once())
            ->method('recognize')
            ->with('scan.jpg', ['question_range' => ['start' => 9, 'end' => 17]])
            ->willReturn([
                'question_results' => [
                    [
                        'question_id' => 109,
                        'question_number' => 9,
                        'type' => 'single',
                        'selected_answer_ids' => [],
                        'selected_letters' => [],
                        'borderline_letters' => ['A'],
                        'borderline_selected_letters' => [],
                        'borderline_unselected_letters' => ['A'],
                    ],
                    [
                        'question_id' => 116,
                        'question_number' => 16,
                        'type' => 'single',
                        'selected_answer_ids' => [454],
                        'selected_letters' => ['C'],
                        'borderline_letters' => ['C'],
                        'borderline_selected_letters' => ['C'],
                        'borderline_unselected_letters' => [],
                    ],
                    [
                        'question_id' => 117,
                        'question_number' => 17,
                        'type' => 'multiple',
                        'selected_answer_ids' => [455, 456],
                        'selected_letters' => ['B', 'C'],
                        'borderline_letters' => ['D'],
                        'borderline_selected_letters' => [],
                        'borderline_unselected_letters' => ['D'],
                    ],
                ],
                'warnings' => [],
            ]);

        $service = new class(
            $this->createMock(BlankFormService::class),
            $this->createMock(GradingService::class),
            $this->createMock(ScanPreviewService::class),
            $this->createMock(BlankSheetManifestService::class),
            $pythonCellOcrService,
            $this->createMock(BlankSheetQrCodeService::class),
            $this->createMock(TestVariantService::class),
        ) extends BlankScanService {
            public function extractForTest(string $imagePath, array $manifest, int $pageNumber): array
            {
                return $this->extractAnswersViaPython($imagePath, $manifest, $pageNumber);
            }
        };

        $result = $service->extractForTest('scan.jpg', ['question_range' => ['start' => 9, 'end' => 17]], 1);

        $this->assertSame([], $result['question_answers'][109]);
        $this->assertSame([454], $result['question_answers'][116]);
        $this->assertSame([455, 456], $result['question_answers'][117]);
        $this->assertSame(['A'], $result['display_answers'][0]['borderline']);
        $this->assertSame(['C'], $result['display_answers'][1]['borderline_selected']);
        $this->assertSame(['D'], $result['display_answers'][2]['borderline_unselected']);
        $this->assertSame([
            'Question 9 has weak borderline marks that stayed below the threshold: A.',
            'Question 16 was recognized from weak borderline marks: C.',
            'Question 17 has weak borderline marks that stayed below the threshold: D.',
        ], $result['warnings']);
        $this->assertSame(['start' => 9, 'end' => 17], $result['question_range']);
    }
}
