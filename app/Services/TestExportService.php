<?php

namespace App\Services;

use App\Models\Test;
use App\Support\BlankScanLayout;
use App\Support\SimpleXlsx;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonException;

class TestExportService
{
    public function buildJsonPayload(Test $test): array
    {
        $test = $test->loadMissing('questions.answers');
        $variantCount = max(1, (int) ($test->variant_count ?: 1));
        $includeQuestionVariant = $variantCount > 1;

        return [
            'title' => $test->title,
            'subject_name' => $test->subject_name,
            'description' => $test->description,
            'time_limit' => $test->time_limit,
            'variant_count' => $variantCount,
            'grade_criteria' => collect($test->grade_criteria ?? [])->values()->all(),
            'questions' => $this->orderedQuestions($test)
                ->map(function ($question) use ($includeQuestionVariant) {
                    $payload = [
                        'question_text' => $question->question_text,
                        'type' => $question->type,
                        'points' => (int) ($question->points ?? 1),
                        'answers' => $question->answers
                            ->sortBy('order')
                            ->values()
                            ->map(fn ($answer) => [
                                'answer_text' => $answer->answer_text,
                                'is_correct' => (bool) $answer->is_correct,
                            ])
                            ->all(),
                    ];

                    if ($includeQuestionVariant) {
                        $payload['variant'] = (int) ($question->variant_number ?? 1);
                    }

                    return $payload;
                })
                ->all(),
        ];
    }

    public function buildSpreadsheetPath(Test $test): string
    {
        $payload = $this->buildJsonPayload($test);
        $answerColumns = collect(BlankScanLayout::answerLetters())
            ->map(fn (string $letter) => 'answer_' . Str::lower($letter))
            ->all();
        $rows = [
            ['title', (string) ($payload['title'] ?? '')],
            ['subject_name', (string) ($payload['subject_name'] ?? '')],
            ['description', (string) ($payload['description'] ?? '')],
            ['time_limit', (string) ($payload['time_limit'] ?? '')],
            ['variant_count', (string) ($payload['variant_count'] ?? 1)],
            ['grade_criteria_json', $this->encodeGradeCriteria($payload['grade_criteria'] ?? [])],
            [],
            array_merge(['question_text', 'variant', 'type', 'points'], $answerColumns, ['correct']),
        ];

        foreach ($payload['questions'] ?? [] as $question) {
            $answers = collect($question['answers'] ?? [])
                ->values();
            $correctLetters = $answers
                ->map(function (array $answer, int $index) {
                    if (!($answer['is_correct'] ?? false)) {
                        return null;
                    }

                    return BlankScanLayout::answerLetters()[$index] ?? null;
                })
                ->filter()
                ->values()
                ->implode(',');

            $answerTexts = BlankScanLayout::answerLetters();
            $row = [
                (string) ($question['question_text'] ?? ''),
                array_key_exists('variant', $question) ? (string) ($question['variant'] ?? '') : '',
                (string) ($question['type'] ?? 'single'),
                (string) ($question['points'] ?? 1),
            ];

            foreach ($answerTexts as $index => $letter) {
                $row[] = (string) ($answers[$index]['answer_text'] ?? '');
            }

            $row[] = $correctLetters;
            $rows[] = $row;
        }

        return SimpleXlsx::writeWorkbook('Тест', $rows);
    }

    public function buildDownloadFileName(Test $test, string $extension): string
    {
        $safeTitle = Str::of((string) ($test->title ?: 'test'))
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9]+/u', '-')
            ->trim('-')
            ->lower()
            ->value();

        $baseName = $safeTitle !== '' ? $safeTitle : ('test-' . $test->id);

        return $baseName . '.' . ltrim(Str::lower($extension), '.');
    }

    protected function orderedQuestions(Test $test): Collection
    {
        return $test->questions
            ->sortBy([
                fn ($question) => (int) ($question->variant_number ?? 1),
                fn ($question) => (int) ($question->order ?? 0),
                fn ($question) => (int) $question->id,
            ])
            ->values();
    }

    protected function encodeGradeCriteria(array $gradeCriteria): string
    {
        try {
            return json_encode($gradeCriteria, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '[]';
        }
    }
}
