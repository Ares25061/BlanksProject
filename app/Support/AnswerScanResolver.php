<?php

namespace App\Support;

class AnswerScanResolver
{
    private const MIN_MARK_SCORE = 0.12;
    private const MIN_MULTIPLE_MARK_SCORE = 0.21;
    private const DYNAMIC_MARGIN = 0.06;
    private const MAX_DYNAMIC_THRESHOLD = 0.22;
    private const DOMINANT_GAP = 0.07;
    private const DOMINANT_RATIO = 0.92;
    private const DOMINANT_SINGLE_SCORE = 0.18;
    private const DOMINANT_DARKNESS_GAP = 0.005;
    private const DOMINANT_DARKNESS_SCORE = 0.20;
    private const DOMINANT_DARKNESS_MAX_SCORE = 0.34;
    private const FALLBACK_SCORE = 0.16;
    private const FALLBACK_SPREAD_SCORE = 0.13;
    private const FALLBACK_SPREAD_GAP = 0.05;
    private const LOW_CONFIDENCE_MULTI_MARK_SCORE = 0.18;
    private const LOW_CONFIDENCE_MULTI_MARK_GAP = 0.03;
    private const LOW_CONFIDENCE_ROW_SCORE = 0.20;
    private const LOW_CONFIDENCE_ROW_GAP = 0.06;
    private const LOW_CONFIDENCE_SINGLE_SCORE = 0.26;
    private const LOW_CONFIDENCE_SINGLE_DARKNESS = 0.20;
    private const LOW_CONFIDENCE_SINGLE_INK = 0.006;
    private const COLOR_MODE_MIN_MARK_SCORE = 0.11;
    private const COLOR_MODE_MIN_MULTIPLE_MARK_SCORE = 0.11;
    private const COLOR_MODE_MAX_DYNAMIC_THRESHOLD = 0.19;
    private const COLOR_MODE_FALLBACK_SCORE = 0.11;
    private const COLOR_MODE_FALLBACK_SPREAD_SCORE = 0.08;
    private const COLOR_MODE_FALLBACK_SPREAD_GAP = 0.04;
    private const COLOR_INK_MODE_THRESHOLD = 0.015;
    private const LOW_INK_STRONG_CUTOFF = 0.0025;
    private const LOW_INK_MID_CUTOFF = 0.006;
    private const LOW_INK_STRONG_BASE_SCORE = 0.34;
    private const LOW_INK_MID_BASE_SCORE = 0.24;
    private const LOW_INK_STRONG_FACTOR = 0.15;
    private const LOW_INK_MID_FACTOR = 0.55;
    private const SINGLE_KEEP_MULTI_SECONDARY_INK = 0.005;
    private const SINGLE_KEEP_MULTI_SECONDARY_SCORE = 0.30;
    private const SINGLE_DOMINANT_SCORE = 0.26;
    private const SINGLE_DOMINANT_GAP = 0.04;
    private const SINGLE_PRIMARY_INK = 0.02;
    private const SINGLE_TINY_SECONDARY_INK = 0.002;
    private const BLACK_MARK_CLEANUP_TOP_SCORE = 0.60;
    private const BLACK_MARK_CLEANUP_MAX_RELATIVE_SCORE = 0.50;
    private const BLACK_MARK_CLEANUP_MAX_CORE_STRONG_RATIO = 0.45;
    private const BLACK_MARK_CLEANUP_MAX_INK = 0.006;

    public static function resolve(string $questionType, array $cellMeasurements): array
    {
        if (empty($cellMeasurements)) {
            return [
                'selected_indexes' => [],
                'ambiguous' => false,
            ];
        }

        $measurements = array_values(array_map(
            fn (array $measurement, int $index) => [
                'option_index' => (int) ($measurement['option_index'] ?? $index),
                'dark_ratio' => (float) ($measurement['dark_ratio'] ?? 0.0),
                'darkness' => (float) ($measurement['darkness'] ?? 0.0),
                'base_score' => self::buildBaseMarkScore($measurement),
                'ink_signal' => self::inkSignal($measurement),
                'score' => isset($measurement['score'])
                    ? (float) $measurement['score']
                    : self::buildMarkScore($measurement),
            ],
            $cellMeasurements,
            array_keys($cellMeasurements),
        ));

        $maxInkSignal = count($measurements) > 0
            ? max(array_column($measurements, 'ink_signal'))
            : 0.0;

        if ($maxInkSignal >= self::COLOR_INK_MODE_THRESHOLD) {
            $measurements = array_values(array_map(function (array $measurement): array {
                if (
                    $measurement['ink_signal'] < self::LOW_INK_STRONG_CUTOFF
                    && $measurement['base_score'] < self::LOW_INK_STRONG_BASE_SCORE
                ) {
                    $measurement['score'] *= self::LOW_INK_STRONG_FACTOR;
                } elseif (
                    $measurement['ink_signal'] < self::LOW_INK_MID_CUTOFF
                    && $measurement['base_score'] < self::LOW_INK_MID_BASE_SCORE
                ) {
                    $measurement['score'] *= self::LOW_INK_MID_FACTOR;
                }

                return $measurement;
            }, $measurements));
        }

        $colorMode = $maxInkSignal >= self::COLOR_INK_MODE_THRESHOLD;

        usort($measurements, fn (array $left, array $right) => $right['score'] <=> $left['score']);

        $scores = array_column($measurements, 'score');
        $topScore = (float) ($scores[0] ?? 0.0);
        $secondScore = (float) ($scores[1] ?? 0.0);
        $minScore = (float) min($scores);
        $averageScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0.0;
        $dynamicThreshold = max(
            $colorMode ? self::COLOR_MODE_MIN_MARK_SCORE : self::MIN_MARK_SCORE,
            min(
                $colorMode ? self::COLOR_MODE_MAX_DYNAMIC_THRESHOLD : self::MAX_DYNAMIC_THRESHOLD,
                $minScore + self::DYNAMIC_MARGIN
            ),
        );

        if ($questionType === 'multiple') {
            $dynamicThreshold = max(
                $dynamicThreshold,
                $colorMode ? self::COLOR_MODE_MIN_MULTIPLE_MARK_SCORE : self::MIN_MULTIPLE_MARK_SCORE
            );
        }

        $selected = array_values(array_filter(
            $measurements,
            fn (array $measurement) => $measurement['score'] >= $dynamicThreshold,
        ));

        if (empty($selected) && self::shouldSelectFallback($topScore, $minScore, $colorMode)) {
            $selected = [$measurements[0]];
        }

        if (
            count($selected) > 1
            && $topScore < self::LOW_CONFIDENCE_MULTI_MARK_SCORE
            && ($topScore - $secondScore) < self::LOW_CONFIDENCE_MULTI_MARK_GAP
        ) {
            $selected = [];
        }

        if (
            !empty($selected)
            && $topScore < self::LOW_CONFIDENCE_ROW_SCORE
            && ($topScore - $averageScore) < self::LOW_CONFIDENCE_ROW_GAP
        ) {
            $selected = [];
        }

        if (!$colorMode && $questionType === 'multiple' && count($selected) > 1) {
            $topSelected = $selected[0];

            $selected = array_values(array_filter($selected, function (array $measurement) use ($topSelected): bool {
                if ($measurement['option_index'] === $topSelected['option_index']) {
                    return true;
                }

                $relativeScore = $topSelected['score'] > 0
                    ? ($measurement['score'] / $topSelected['score'])
                    : 0.0;

                return !(
                    $topSelected['score'] >= self::BLACK_MARK_CLEANUP_TOP_SCORE
                    && ($measurement['ink_signal'] ?? 0.0) < self::BLACK_MARK_CLEANUP_MAX_INK
                    && ($measurement['core_strong_ratio'] ?? 0.0) < self::BLACK_MARK_CLEANUP_MAX_CORE_STRONG_RATIO
                    && $relativeScore < self::BLACK_MARK_CLEANUP_MAX_RELATIVE_SCORE
                );
            }));
        }

        $ambiguous = false;

        if ($questionType === 'single' && count($selected) > 1) {
            $first = $selected[0];
            $second = $selected[1];
            $relativeScore = $first['score'] > 0
                ? ($second['score'] / $first['score'])
                : 1.0;
            $secondInk = (float) ($second['ink_signal'] ?? 0.0);
            $firstInk = (float) ($first['ink_signal'] ?? 0.0);
            $keepMultipleByInk = $secondInk >= self::SINGLE_KEEP_MULTI_SECONDARY_INK
                && $second['score'] >= self::SINGLE_KEEP_MULTI_SECONDARY_SCORE;

            if ($colorMode) {
                if (
                    (
                        !$keepMultipleByInk
                        && (
                            ($first['score'] - $second['score']) >= self::SINGLE_DOMINANT_GAP
                            || $first['score'] >= self::SINGLE_DOMINANT_SCORE
                        )
                    )
                    || (
                        $firstInk > self::SINGLE_PRIMARY_INK
                        && $secondInk < self::SINGLE_TINY_SECONDARY_INK
                    )
                ) {
                    $selected = [$first];
                } else {
                    $ambiguous = true;
                }
            } elseif (
                (
                    ($first['score'] - $second['score']) >= self::DOMINANT_GAP
                    || (
                        $first['score'] >= self::DOMINANT_SINGLE_SCORE
                        && $first['darkness'] >= $second['darkness']
                        && $relativeScore <= self::DOMINANT_RATIO
                    )
                )
            ) {
                $selected = [$first];
            } else {
                $selectedByDarkness = $selected;
                usort($selectedByDarkness, fn (array $left, array $right) => $right['darkness'] <=> $left['darkness']);
                $firstByDarkness = $selectedByDarkness[0];
                $secondByDarkness = $selectedByDarkness[1] ?? null;

                if (
                    $firstByDarkness['darkness'] >= self::DOMINANT_DARKNESS_SCORE
                    && $firstByDarkness['score'] <= self::DOMINANT_DARKNESS_MAX_SCORE
                    && (
                        !$secondByDarkness
                        || (($firstByDarkness['darkness'] - $secondByDarkness['darkness']) >= self::DOMINANT_DARKNESS_GAP)
                    )
                ) {
                    $selected = [$firstByDarkness];
                } else {
                    $ambiguous = true;
                }
            }
        }

        if (
            $questionType === 'single'
            && count($selected) === 1
            && $selected[0]['score'] < self::LOW_CONFIDENCE_SINGLE_SCORE
            && $selected[0]['darkness'] < self::LOW_CONFIDENCE_SINGLE_DARKNESS
            && ($selected[0]['ink_signal'] ?? 0.0) < self::LOW_CONFIDENCE_SINGLE_INK
        ) {
            $selected = [];
            $ambiguous = false;
        }

        return [
            'selected_indexes' => self::selectedIndexes($selected),
            'ambiguous' => $ambiguous,
        ];
    }

    public static function buildMarkScore(array $measurement): float
    {
        $baseScore = self::buildBaseMarkScore($measurement);
        $inkRatio = (float) ($measurement['ink_ratio'] ?? 0.0);
        $coreInkRatio = (float) ($measurement['core_ink_ratio'] ?? 0.0);

        return $baseScore
            + ($inkRatio * 7.0)
            + ($coreInkRatio * 5.0);
    }

    private static function buildBaseMarkScore(array $measurement): float
    {
        $darkRatio = (float) ($measurement['dark_ratio'] ?? 0.0);
        $darkness = (float) ($measurement['darkness'] ?? 0.0);
        $baseScore = ($darkRatio * 0.7) + ($darkness * 0.3);

        if (
            !array_key_exists('core_dark_ratio', $measurement)
            && !array_key_exists('core_strong_ratio', $measurement)
        ) {
            return $baseScore;
        }

        $coreDarkRatio = (float) ($measurement['core_dark_ratio'] ?? 0.0);
        $coreStrongRatio = (float) ($measurement['core_strong_ratio'] ?? 0.0);

        return ($baseScore * 0.65)
            + ($coreDarkRatio * 0.25)
            + ($coreStrongRatio * 0.10);
    }

    private static function inkSignal(array $measurement): float
    {
        return (float) ($measurement['ink_signal']
            ?? (($measurement['ink_ratio'] ?? 0.0) + ($measurement['core_ink_ratio'] ?? 0.0)));
    }

    private static function shouldSelectFallback(float $topScore, float $minScore, bool $colorMode = false): bool
    {
        $spread = $topScore - $minScore;

        if ($colorMode) {
            return $topScore >= self::COLOR_MODE_FALLBACK_SCORE
                || ($topScore >= self::COLOR_MODE_FALLBACK_SPREAD_SCORE && $spread >= self::COLOR_MODE_FALLBACK_SPREAD_GAP);
        }

        return $topScore >= self::FALLBACK_SCORE
            || ($topScore >= self::FALLBACK_SPREAD_SCORE && $spread >= self::FALLBACK_SPREAD_GAP);
    }

    private static function selectedIndexes(array $selected): array
    {
        $indexes = array_map(
            fn (array $measurement) => (int) $measurement['option_index'],
            $selected,
        );

        sort($indexes);

        return array_values($indexes);
    }
}
