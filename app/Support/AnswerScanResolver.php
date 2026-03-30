<?php

namespace App\Support;

class AnswerScanResolver
{
    private const MIN_MARK_SCORE = 0.12;
    private const MIN_MULTIPLE_MARK_SCORE = 0.21;
    private const DYNAMIC_MARGIN = 0.06;
    private const MAX_DYNAMIC_THRESHOLD = 0.22;
    private const DOMINANT_GAP = 0.07;
    private const FALLBACK_SCORE = 0.16;
    private const FALLBACK_SPREAD_SCORE = 0.13;
    private const FALLBACK_SPREAD_GAP = 0.05;

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
                'score' => isset($measurement['score'])
                    ? (float) $measurement['score']
                    : self::buildMarkScore($measurement),
            ],
            $cellMeasurements,
            array_keys($cellMeasurements),
        ));

        usort($measurements, fn (array $left, array $right) => $right['score'] <=> $left['score']);

        $scores = array_column($measurements, 'score');
        $topScore = (float) ($scores[0] ?? 0.0);
        $minScore = (float) min($scores);
        $dynamicThreshold = max(
            self::MIN_MARK_SCORE,
            min(self::MAX_DYNAMIC_THRESHOLD, $minScore + self::DYNAMIC_MARGIN),
        );

        if ($questionType === 'multiple') {
            $dynamicThreshold = max($dynamicThreshold, self::MIN_MULTIPLE_MARK_SCORE);
        }

        $selected = array_values(array_filter(
            $measurements,
            fn (array $measurement) => $measurement['score'] >= $dynamicThreshold,
        ));

        if (empty($selected) && self::shouldSelectFallback($topScore, $minScore)) {
            $selected = [$measurements[0]];
        }

        $ambiguous = false;

        if ($questionType === 'single' && count($selected) > 1) {
            $first = $selected[0];
            $second = $selected[1];

            if (($first['score'] - $second['score']) >= self::DOMINANT_GAP) {
                $selected = [$first];
            } else {
                $ambiguous = true;
            }
        }

        return [
            'selected_indexes' => self::selectedIndexes($selected),
            'ambiguous' => $ambiguous,
        ];
    }

    public static function buildMarkScore(array $measurement): float
    {
        $darkRatio = (float) ($measurement['dark_ratio'] ?? 0.0);
        $darkness = (float) ($measurement['darkness'] ?? 0.0);

        return ($darkRatio * 0.7) + ($darkness * 0.3);
    }

    private static function shouldSelectFallback(float $topScore, float $minScore): bool
    {
        $spread = $topScore - $minScore;

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
