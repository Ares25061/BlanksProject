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

    public function test_low_confidence_flat_noise_stays_unanswered(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'score' => 0.161],
            ['option_index' => 1, 'score' => 0.160],
            ['option_index' => 2, 'score' => 0.158],
            ['option_index' => 3, 'score' => 0.140],
            ['option_index' => 4, 'score' => 0.001],
        ]);

        $this->assertSame([], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_low_confidence_row_leader_stays_unanswered(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'score' => 0.191],
            ['option_index' => 1, 'score' => 0.149],
            ['option_index' => 2, 'score' => 0.145],
            ['option_index' => 3, 'score' => 0.139],
        ]);

        $this->assertSame([], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_single_choice_prefers_colored_mark_over_dark_border_noise(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.20, 'darkness' => 0.16, 'ink_ratio' => 0.0005, 'core_ink_ratio' => 0.0],
            ['option_index' => 1, 'dark_ratio' => 0.29, 'darkness' => 0.19, 'ink_ratio' => 0.0001, 'core_ink_ratio' => 0.0],
            ['option_index' => 2, 'dark_ratio' => 0.43, 'darkness' => 0.30, 'ink_ratio' => 0.0318, 'core_ink_ratio' => 0.0271],
            ['option_index' => 3, 'dark_ratio' => 0.30, 'darkness' => 0.20, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
        ]);

        $this->assertSame([2], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_single_choice_keeps_double_selection_when_second_mark_has_real_ink(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.52, 'darkness' => 0.32, 'ink_ratio' => 0.1307, 'core_ink_ratio' => 0.0482],
            ['option_index' => 1, 'dark_ratio' => 0.28, 'darkness' => 0.20, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
            ['option_index' => 2, 'dark_ratio' => 0.38, 'darkness' => 0.24, 'ink_ratio' => 0.0144, 'core_ink_ratio' => 0.0036],
            ['option_index' => 3, 'dark_ratio' => 0.28, 'darkness' => 0.21, 'ink_ratio' => 0.0001, 'core_ink_ratio' => 0.0],
        ]);

        $this->assertSame([0, 2], $result['selected_indexes']);
        $this->assertTrue($result['ambiguous']);
    }

    public function test_low_confidence_colored_single_mark_is_not_dropped(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.06, 'darkness' => 0.08, 'ink_ratio' => 0.0109, 'core_ink_ratio' => 0.0072],
            ['option_index' => 1, 'dark_ratio' => 0.0, 'darkness' => 0.0026, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
            ['option_index' => 2, 'dark_ratio' => 0.0, 'darkness' => 0.0030, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
            ['option_index' => 3, 'dark_ratio' => 0.0, 'darkness' => 0.0032, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
        ]);

        $this->assertSame([0], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_single_choice_low_ink_row_noise_stays_unanswered(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.10, 'darkness' => 0.0629, 'ink_ratio' => 0.0001, 'core_ink_ratio' => 0.0],
            ['option_index' => 1, 'dark_ratio' => 0.20, 'darkness' => 0.1548, 'ink_ratio' => 0.0011, 'core_ink_ratio' => 0.0001],
            ['option_index' => 2, 'dark_ratio' => 0.20, 'darkness' => 0.1248, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
            ['option_index' => 3, 'dark_ratio' => 0.20, 'darkness' => 0.1662, 'ink_ratio' => 0.0010, 'core_ink_ratio' => 0.0],
        ]);

        $this->assertSame([], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_single_choice_jpeg_tail_noise_stays_unanswered(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.1337, 'darkness' => 0.1436, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0013, 'core_ink_ratio' => 0.0008],
            ['option_index' => 1, 'dark_ratio' => 0.2333, 'darkness' => 0.1862, 'core_dark_ratio' => 0.1667, 'core_strong_ratio' => 0.1667, 'ink_ratio' => 0.0025, 'core_ink_ratio' => 0.0015],
            ['option_index' => 2, 'dark_ratio' => 0.1333, 'darkness' => 0.1696, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0006, 'core_ink_ratio' => 0.0003],
            ['option_index' => 3, 'dark_ratio' => 0.1000, 'darkness' => 0.1314, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0005, 'core_ink_ratio' => 0.0002],
        ]);

        $this->assertSame([], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_single_choice_png_tail_noise_stays_unanswered(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.0, 'darkness' => 0.0, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
            ['option_index' => 1, 'dark_ratio' => 0.20, 'darkness' => 0.1514, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.00181, 'core_ink_ratio' => 0.00013],
            ['option_index' => 2, 'dark_ratio' => 0.25, 'darkness' => 0.1617, 'core_dark_ratio' => 0.1111, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
            ['option_index' => 3, 'dark_ratio' => 0.20, 'darkness' => 0.1634, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.00226, 'core_ink_ratio' => 0.0],
        ]);

        $this->assertSame([], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_multiple_choice_png_tail_noise_stays_unanswered(): void
    {
        $result = AnswerScanResolver::resolve('multiple', [
            ['option_index' => 0, 'dark_ratio' => 0.03, 'darkness' => 0.0330, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.00002, 'core_ink_ratio' => 0.0],
            ['option_index' => 1, 'dark_ratio' => 0.29, 'darkness' => 0.1750, 'core_dark_ratio' => 0.0833, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.00135, 'core_ink_ratio' => 0.0],
            ['option_index' => 2, 'dark_ratio' => 0.20, 'darkness' => 0.1491, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.00163, 'core_ink_ratio' => 0.00001],
            ['option_index' => 3, 'dark_ratio' => 0.14, 'darkness' => 0.1087, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
        ]);

        $this->assertSame([], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_real_low_score_colored_mark_is_kept(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.1770, 'darkness' => 0.1770, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0180, 'core_ink_ratio' => 0.0040],
            ['option_index' => 1, 'dark_ratio' => 0.06, 'darkness' => 0.07, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
            ['option_index' => 2, 'dark_ratio' => 0.05, 'darkness' => 0.06, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
            ['option_index' => 3, 'dark_ratio' => 0.05, 'darkness' => 0.06, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
        ]);

        $this->assertSame([0], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_multiple_choice_strong_black_marks_do_not_pull_empty_bridge_cells(): void
    {
        $result = AnswerScanResolver::resolve('multiple', [
            ['option_index' => 0, 'core_strong_ratio' => 0.8667, 'ink_ratio' => 0.0002, 'core_ink_ratio' => 0.0, 'score' => 0.7983],
            ['option_index' => 1, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0, 'score' => 0.2219],
            ['option_index' => 2, 'core_strong_ratio' => 0.3667, 'ink_ratio' => 0.0021, 'core_ink_ratio' => 0.0028, 'score' => 0.3453],
            ['option_index' => 3, 'core_strong_ratio' => 0.6333, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0, 'score' => 0.6099],
        ]);

        $this->assertSame([0, 3], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_multiple_choice_strong_black_mark_does_not_pull_neighbor_shadow(): void
    {
        $result = AnswerScanResolver::resolve('multiple', [
            ['option_index' => 0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0, 'score' => 0.0010],
            ['option_index' => 1, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0016, 'core_ink_ratio' => 0.0, 'score' => 0.1398],
            ['option_index' => 2, 'core_strong_ratio' => 0.2, 'ink_ratio' => 0.0006, 'core_ink_ratio' => 0.0006, 'score' => 0.2266],
            ['option_index' => 3, 'core_strong_ratio' => 0.9667, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0, 'score' => 0.7980],
        ]);

        $this->assertSame([3], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_multiple_choice_jpeg_bridge_noise_is_removed_between_two_strong_black_marks(): void
    {
        $result = AnswerScanResolver::resolve('multiple', [
            ['option_index' => 0, 'core_strong_ratio' => 0.8667, 'ink_ratio' => 0.0008, 'core_ink_ratio' => 0.0007, 'score' => 0.8062],
            ['option_index' => 1, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0, 'score' => 0.2303],
            ['option_index' => 2, 'core_strong_ratio' => 0.3333, 'ink_ratio' => 0.0022, 'core_ink_ratio' => 0.0034, 'score' => 0.3455],
            ['option_index' => 3, 'core_strong_ratio' => 0.6333, 'ink_ratio' => 0.0017, 'core_ink_ratio' => 0.0017, 'score' => 0.6249],
        ]);

        $this->assertSame([0, 3], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_single_choice_keeps_weak_but_real_second_mark_when_ink_is_present(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.51, 'darkness' => 0.3195, 'core_dark_ratio' => 0.1667, 'core_strong_ratio' => 0.0556, 'ink_ratio' => 0.0784, 'core_ink_ratio' => 0.0569],
            ['option_index' => 1, 'dark_ratio' => 0.28, 'darkness' => 0.1984, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
            ['option_index' => 2, 'dark_ratio' => 0.37, 'darkness' => 0.2333, 'core_dark_ratio' => 0.3333, 'core_strong_ratio' => 0.1667, 'ink_ratio' => 0.0058, 'core_ink_ratio' => 0.0],
            ['option_index' => 3, 'dark_ratio' => 0.28, 'darkness' => 0.2052, 'core_dark_ratio' => 0.1667, 'core_strong_ratio' => 0.1667, 'ink_ratio' => 0.0, 'core_ink_ratio' => 0.0],
        ]);

        $this->assertSame([0, 2], $result['selected_indexes']);
        $this->assertTrue($result['ambiguous']);
    }

    public function test_single_choice_does_not_keep_weak_neighbor_when_second_mark_is_too_soft(): void
    {
        $result = AnswerScanResolver::resolve('single', [
            ['option_index' => 0, 'dark_ratio' => 0.41, 'darkness' => 0.2501, 'core_dark_ratio' => 0.0833, 'core_strong_ratio' => 0.0278, 'ink_ratio' => 0.0441, 'core_ink_ratio' => 0.0321],
            ['option_index' => 1, 'dark_ratio' => 0.29, 'darkness' => 0.2033, 'core_dark_ratio' => 0.0, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0009, 'core_ink_ratio' => 0.0001],
            ['option_index' => 2, 'dark_ratio' => 0.27, 'darkness' => 0.2106, 'core_dark_ratio' => 0.1667, 'core_strong_ratio' => 0.1667, 'ink_ratio' => 0.0021, 'core_ink_ratio' => 0.0033],
            ['option_index' => 3, 'dark_ratio' => 0.36, 'darkness' => 0.2146, 'core_dark_ratio' => 0.0833, 'core_strong_ratio' => 0.0, 'ink_ratio' => 0.0008, 'core_ink_ratio' => 0.0002],
        ]);

        $this->assertSame([0], $result['selected_indexes']);
        $this->assertFalse($result['ambiguous']);
    }

}
