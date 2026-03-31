<?php

namespace App\Services;

use App\Models\BlankForm;
use App\Models\Test;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TestVariantService
{
    public const MIN_VARIANTS = 1;
    public const MAX_VARIANTS = 10;

    public function normalizeVariantCount(?int $variantCount): int
    {
        return max(self::MIN_VARIANTS, min(self::MAX_VARIANTS, (int) ($variantCount ?: 1)));
    }

    public function normalizeVariantNumber(Test $test, ?int $variantNumber): int
    {
        $count = $this->normalizeVariantCount($test->variant_count ?? 1);
        $normalized = (int) ($variantNumber ?: 1);

        return max(1, min($count, $normalized));
    }

    public function validateVariantNumber(Test $test, ?int $variantNumber, string $key = 'variant_number'): int
    {
        $normalized = (int) ($variantNumber ?: 1);
        $count = $this->normalizeVariantCount($test->variant_count ?? 1);

        if ($normalized < 1 || $normalized > $count) {
            throw ValidationException::withMessages([
                $key => 'Номер варианта должен быть от 1 до ' . $count . '.',
            ]);
        }

        return $normalized;
    }

    public function orderedAnswersForQuestion($question, int $variantNumber): Collection
    {
        return $question->answers->sortBy('order')->values();
    }

    public function questionsForVariant(Test $test, ?int $variantNumber = null): Collection
    {
        $normalizedVariantNumber = $this->normalizeVariantNumber($test, $variantNumber);
        $questions = $test->questions->sortBy('order')->values();

        if ($this->normalizeVariantCount($test->variant_count ?? 1) <= 1) {
            return $questions->each(fn ($question) => $question->setAttribute('variant_number', 1))->values();
        }

        return $questions
            ->filter(fn ($question) => (int) ($question->variant_number ?? 1) === $normalizedVariantNumber)
            ->values();
    }

    public function attachVariantAnswers(BlankForm $blankForm): BlankForm
    {
        $variantNumber = $this->normalizeVariantNumber($blankForm->test, $blankForm->variant_number);
        $variantQuestions = $this->questionsForVariant($blankForm->test, $variantNumber)->values();

        $variantQuestions->each(function ($question) use ($variantNumber) {
            $variantAnswers = $this->orderedAnswersForQuestion($question, $variantNumber)
                ->map(fn ($answer) => $answer->toArray())
                ->values()
                ->all();

            $question->setAttribute('variant_answers', $variantAnswers);
        });

        $blankForm->test->setRelation('questions', $variantQuestions);
        $blankForm->setAttribute('variant_number', $variantNumber);

        return $blankForm;
    }

    public function buildBalancedVariantNumbers(Test $test, int $count): array
    {
        $variantCount = $this->normalizeVariantCount($test->variant_count ?? 1);
        $assignments = [];

        for ($index = 0; $index < $count; $index++) {
            $assignments[] = ($index % $variantCount) + 1;
        }

        return $assignments;
    }
}
