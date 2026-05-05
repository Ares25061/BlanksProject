<?php

namespace App\Services;

use App\Models\BlankForm;
use App\Models\Test;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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

    public function orderedAnswersForQuestion($question, int $variantNumber, bool $shuffle = false, array $shuffleIdentity = []): Collection
    {
        $answers = $question->answers->sortBy('order')->values();

        if (!$shuffle || $answers->count() <= 1) {
            return $answers;
        }

        return $this->deterministicallyShuffleAnswers($answers, $question, $variantNumber, $shuffleIdentity);
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
        $shuffleAnswers = (bool) data_get($blankForm->metadata, 'print_options.shuffle_answer_options', false);
        $shuffleIdentity = $this->blankFormShuffleIdentity($blankForm);
        $printedAnswerOrder = $this->printedAnswerOrderByQuestion($blankForm);

        $variantQuestions->each(function ($question) use ($variantNumber, $shuffleAnswers, $shuffleIdentity, $printedAnswerOrder) {
            $variantAnswers = $this->orderedAnswersForPrintedBlank(
                $question,
                $variantNumber,
                $shuffleAnswers,
                $shuffleIdentity,
                $printedAnswerOrder[(int) $question->id] ?? null
            )
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

    public function blankFormShuffleIdentity(BlankForm $blankForm): array
    {
        return [
            'blank_form_id' => (int) $blankForm->id,
            'form_number' => (string) $blankForm->form_number,
            'test_id' => (int) $blankForm->test_id,
            'variant_number' => (int) ($blankForm->variant_number ?? 1),
        ];
    }

    public function printedAnswerOrderByQuestion(BlankForm $blankForm): array
    {
        $orders = [];

        foreach (data_get($blankForm->metadata, 'print_layout.pages', []) as $page) {
            $manifestPath = (string) ($page['manifest_path'] ?? '');

            if ($manifestPath === '' || !Storage::disk('local')->exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) Storage::disk('local')->get($manifestPath), true);

            if (!is_array($manifest)) {
                continue;
            }

            foreach (($manifest['questions'] ?? []) as $question) {
                $questionId = (int) ($question['question_id'] ?? 0);

                if ($questionId <= 0) {
                    continue;
                }

                $answerIds = collect($question['cells'] ?? [])
                    ->sortBy(fn (array $cell) => (int) ($cell['option_index'] ?? 0))
                    ->pluck('answer_id')
                    ->map(fn ($answerId) => (int) $answerId)
                    ->filter()
                    ->values()
                    ->all();

                if ($answerIds !== []) {
                    $orders[$questionId] = $answerIds;
                }
            }
        }

        return $orders;
    }

    private function orderedAnswersForPrintedBlank($question, int $variantNumber, bool $shuffle, array $shuffleIdentity, ?array $printedAnswerIds): Collection
    {
        if ($printedAnswerIds !== null) {
            $orderedAnswers = $this->orderedAnswersByIds($question, $printedAnswerIds);

            if ($orderedAnswers->isNotEmpty()) {
                return $orderedAnswers;
            }
        }

        return $this->orderedAnswersForQuestion($question, $variantNumber, $shuffle, $shuffleIdentity);
    }

    private function orderedAnswersByIds($question, array $answerIds): Collection
    {
        $answers = $question->answers->sortBy('order')->values();
        $answersById = $answers->keyBy(fn ($answer) => (int) $answer->id);
        $orderedAnswers = collect($answerIds)
            ->map(fn ($answerId) => $answersById->get((int) $answerId))
            ->filter()
            ->unique(fn ($answer) => (int) $answer->id)
            ->values();

        $orderedAnswerIds = $orderedAnswers
            ->map(fn ($answer) => (int) $answer->id)
            ->all();

        return $orderedAnswers
            ->concat($answers->reject(fn ($answer) => in_array((int) $answer->id, $orderedAnswerIds, true)))
            ->values();
    }

    private function deterministicallyShuffleAnswers(Collection $answers, $question, int $variantNumber, array $shuffleIdentity): Collection
    {
        $seedBase = json_encode([
            'identity' => $shuffleIdentity,
            'variant_number' => $variantNumber,
            'question_id' => (int) $question->id,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $shuffled = $answers
            ->sortBy(fn ($answer) => sha1($seedBase . '|' . (int) $answer->id))
            ->values();

        if ($shuffled->pluck('id')->all() !== $answers->pluck('id')->all()) {
            return $shuffled;
        }

        $offset = (hexdec(substr(sha1($seedBase . '|fallback'), 0, 8)) % ($answers->count() - 1)) + 1;

        return $answers
            ->slice($offset)
            ->concat($answers->slice(0, $offset))
            ->values();
    }
}
