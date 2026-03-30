<?php

namespace Tests\Unit;

use App\Support\AnswerScanResolver;
use PHPUnit\Framework\TestCase;

class AnswerScanResolverTest extends TestCase
{
    public function test_single_choice_prefers_dominant_mark(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.44, 'darkness' => 0.52],
            ['option_index' => 1, 'dark_ratio' => 0.10, 'darkness' => 0.15],
        ]);

        $this->assertSame([0], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_single_choice_keeps_ambiguous_double_mark(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.36, 'darkness' => 0.42],
            ['option_index' => 1, 'dark_ratio' => 0.33, 'darkness' => 0.39],
        ]);

        $this->assertSame([0, 1], $result['selected_indexes']);
        $this->assertTrue($result['ambiguous']);
    }

    public function test_multiple_choice_accepts_two_marked_options(): void
    {
        $result = AnswerScanResolver::resolve('multiple', [
            ['option_index' => 0, 'dark_ratio' => 0.31, 'darkness' => 0.37],
            ['option_index' => 1, 'dark_ratio' => 0.34, 'darkness' => 0.40],
            ['option_index' => 2, 'dark_ratio' => 0.08, 'darkness' => 0.11],
        ]);

        $this->assertSame([0, 1], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_multiple_choice_ignores_weak_neighbor_noise(): void
    {
        $result = AnswerScanResolver::resolve('multiple', [
            ['option_index' => 0, 'score' => 0.101],
            ['option_index' => 1, 'score' => 0.970],
            ['option_index' => 2, 'score' => 0.806],
            ['option_index' => 3, 'score' => 0.192],
        ]);

        $this->assertSame([1, 2], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_multiple_choice_ignores_weak_middle_bridge_noise(): void
    {
        $result = AnswerScanResolver::resolve('multiple', [
            ['option_index' => 0, 'score' => 0.101],
            ['option_index' => 1, 'score' => 0.485],
            ['option_index' => 2, 'score' => 0.180],
            ['option_index' => 3, 'score' => 0.574],
        ]);

        $this->assertSame([1, 3], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_blank_row_stays_unanswered(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.05, 'darkness' => 0.08],
            ['option_index' => 1, 'dark_ratio' => 0.04, 'darkness' => 0.07],
        ]);

        $this->assertSame([], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }
}
