<?php

namespace App\Services;

use App\Support\BlankScanLayout;
use App\Support\SimpleXlsx;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;

class TestImportService
{
    public function importFromUploadedFile(UploadedFile $file): array
    {
        $extension = Str::lower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        return match ($extension) {
            'json' => $this->importFromJsonFile($file),
            'xlsx' => $this->importFromXlsxFile($file),
            default => throw ValidationException::withMessages([
                'file' => 'Поддерживаются только файлы JSON и XLSX.',
            ]),
        };
    }

    private function importFromJsonFile(UploadedFile $file): array
    {
        try {
            $payload = json_decode($file->get(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'file' => 'Не удалось разобрать JSON-файл. Проверьте формат.',
            ]);
        }

        if (!is_array($payload)) {
            throw ValidationException::withMessages([
                'file' => 'JSON должен содержать массив вопросов или объект с полем questions.',
            ]);
        }

        $questionSource = Arr::get($payload, 'questions');
        if (!is_array($questionSource)) {
            $questionSource = array_is_list($payload) ? $payload : [];
        }

        if ($questionSource === []) {
            throw ValidationException::withMessages([
                'questions' => 'В файле не найдено ни одного вопроса для импорта.',
            ]);
        }

        return [
            'title' => $this->nullableString(Arr::get($payload, 'title')),
            'subject_name' => $this->nullableString(Arr::get($payload, 'subject_name')),
            'description' => $this->nullableString(Arr::get($payload, 'description')),
            'time_limit' => $this->nullableInt(Arr::get($payload, 'time_limit')),
            'grade_criteria' => $this->normalizeGradeCriteria(Arr::get($payload, 'grade_criteria', [])),
            'questions' => $this->normalizeQuestions($questionSource),
        ];
    }

    private function importFromXlsxFile(UploadedFile $file): array
    {
        $rows = SimpleXlsx::readRows($file->getRealPath());
        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'file' => 'В XLSX-файле должна быть строка заголовков и хотя бы один вопрос.',
            ]);
        }

        $headers = $this->normalizeHeaders(array_shift($rows));
        $questions = [];

        foreach ($rows as $row) {
            $rowData = [];

            foreach ($headers as $index => $header) {
                if ($header === null) {
                    continue;
                }

                $rowData[$header] = trim((string) ($row[$index] ?? ''));
            }

            if ($this->rowIsEmpty($rowData)) {
                continue;
            }

            $questions[] = $this->normalizeQuestionFromSpreadsheet($rowData, count($questions));
        }

        if ($questions === []) {
            throw ValidationException::withMessages([
                'questions' => 'В XLSX-файле не найдено заполненных строк с вопросами.',
            ]);
        }

        return [
            'title' => null,
            'subject_name' => null,
            'description' => null,
            'time_limit' => null,
            'grade_criteria' => [],
            'questions' => $questions,
        ];
    }

    private function normalizeQuestions(array $questions): array
    {
        $normalized = [];

        foreach ($questions as $index => $question) {
            if (!is_array($question)) {
                throw ValidationException::withMessages([
                    'questions' => 'Каждый вопрос в файле должен быть объектом.',
                ]);
            }

            $normalized[] = $this->normalizeQuestion($question, $index);
        }

        return $normalized;
    }

    private function normalizeQuestion(array $question, int $index): array
    {
        $questionText = $this->nullableString(
            $question['question_text']
            ?? $question['text']
            ?? $question['question']
            ?? $question['title']
            ?? null
        );

        if ($questionText === null) {
            throw ValidationException::withMessages([
                "questions.{$index}.question_text" => 'У одного из импортируемых вопросов не указан текст.',
            ]);
        }

        $answersSource = $question['answers'] ?? $question['options'] ?? $question['variants'] ?? null;
        if (!is_array($answersSource)) {
            throw ValidationException::withMessages([
                "questions.{$index}.answers" => 'У вопроса "' . Str::limit($questionText, 60) . '" не найден список вариантов ответа.',
            ]);
        }

        $correctTokens = $this->correctTokens(
            $question['correct_answers']
            ?? $question['correct']
            ?? $question['correct_answer']
            ?? $question['right_answers']
            ?? []
        );

        $answers = $this->normalizeAnswers($answersSource, $correctTokens, $index);
        $correctCount = collect($answers)->where('is_correct', true)->count();

        return [
            'question_text' => $questionText,
            'type' => $this->normalizeType($question['type'] ?? null, $correctCount),
            'points' => max(1, $this->nullableInt($question['points'] ?? $question['score'] ?? 1) ?? 1),
            'order' => $index,
            'answers' => $answers,
        ];
    }

    private function normalizeQuestionFromSpreadsheet(array $rowData, int $index): array
    {
        $questionText = $this->nullableString($rowData['question_text'] ?? null);
        if ($questionText === null) {
            throw ValidationException::withMessages([
                'file' => 'В одной из строк XLSX отсутствует текст вопроса.',
            ]);
        }

        $answerTexts = collect(BlankScanLayout::answerLetters())
            ->map(fn (string $letter) => $this->nullableString($rowData['answer_' . Str::lower($letter)] ?? null))
            ->filter()
            ->values()
            ->all();

        if (count($answerTexts) < 2) {
            throw ValidationException::withMessages([
                'file' => 'У вопроса "' . Str::limit($questionText, 60) . '" должно быть минимум два варианта ответа.',
            ]);
        }

        $correctTokens = $this->correctTokens($rowData['correct'] ?? '');
        $answers = [];

        foreach ($answerTexts as $answerIndex => $answerText) {
            $letter = BlankScanLayout::answerLetters()[$answerIndex] ?? null;
            $answers[] = [
                'answer_text' => $answerText,
                'is_correct' => $letter ? in_array($letter, $correctTokens, true) : false,
                'order' => $answerIndex,
            ];
        }

        if (!collect($answers)->contains('is_correct', true)) {
            throw ValidationException::withMessages([
                'file' => 'У вопроса "' . Str::limit($questionText, 60) . '" не удалось определить правильный ответ по колонке correct.',
            ]);
        }

        return [
            'question_text' => $questionText,
            'type' => $this->normalizeType($rowData['type'] ?? null, collect($answers)->where('is_correct', true)->count()),
            'points' => max(1, $this->nullableInt($rowData['points'] ?? 1) ?? 1),
            'order' => $index,
            'answers' => $answers,
        ];
    }

    private function normalizeAnswers(array $answersSource, array $correctTokens, int $questionIndex): array
    {
        $answers = [];
        $normalizedTokens = array_map(static fn (string $token) => Str::lower($token), $correctTokens);

        foreach (array_values($answersSource) as $answerIndex => $answer) {
            $answerText = null;
            $isCorrect = false;

            if (is_array($answer)) {
                $answerText = $this->nullableString(
                    $answer['answer_text']
                    ?? $answer['text']
                    ?? $answer['value']
                    ?? $answer['label']
                    ?? null
                );

                $isCorrect = filter_var(
                    $answer['is_correct'] ?? $answer['correct'] ?? $answer['right'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );
            } elseif (is_string($answer) || is_numeric($answer)) {
                $answerText = trim((string) $answer);
            }

            if ($answerText === null) {
                continue;
            }

            $letter = BlankScanLayout::answerLetters()[$answerIndex] ?? null;
            $matchesToken = $letter ? in_array(Str::lower($letter), $normalizedTokens, true) : false;
            if (!$matchesToken && $normalizedTokens !== []) {
                $matchesToken = in_array(Str::lower($answerText), $normalizedTokens, true);
            }

            $answers[] = [
                'answer_text' => $answerText,
                'is_correct' => $isCorrect || $matchesToken,
                'order' => $answerIndex,
            ];
        }

        if (count($answers) < 2) {
            throw ValidationException::withMessages([
                "questions.{$questionIndex}.answers" => 'У импортируемого вопроса должно быть минимум два варианта ответа.',
            ]);
        }

        if (count($answers) > BlankScanLayout::ANSWER_OPTION_COUNT) {
            throw ValidationException::withMessages([
                "questions.{$questionIndex}.answers" => 'У импортируемого вопроса больше ' . BlankScanLayout::ANSWER_OPTION_COUNT . ' вариантов ответа.',
            ]);
        }

        if (!collect($answers)->contains('is_correct', true)) {
            throw ValidationException::withMessages([
                "questions.{$questionIndex}.answers" => 'У импортируемого вопроса должен быть хотя бы один правильный ответ.',
            ]);
        }

        return $answers;
    }

    private function normalizeType(mixed $typeValue, int $correctCount): string
    {
        $normalized = Str::lower(trim((string) $typeValue));

        return match ($normalized) {
            'single', 'one', 'один', 'одиночный' => 'single',
            'multiple', 'multi', 'many', 'несколько', 'множественный' => 'multiple',
            default => $correctCount > 1 ? 'multiple' : 'single',
        };
    }

    private function normalizeGradeCriteria(array $criteria): array
    {
        return collect($criteria)
            ->map(function ($criterion) {
                if (!is_array($criterion)) {
                    return null;
                }

                $label = $this->nullableString($criterion['label'] ?? $criterion['name'] ?? null);
                if ($label === null) {
                    return null;
                }

                return [
                    'label' => $label,
                    'min_points' => max(0, $this->nullableInt($criterion['min_points'] ?? $criterion['points_from'] ?? 0) ?? 0),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $normalized = Str::of((string) $header)
                ->ascii()
                ->lower()
                ->replace(['?', '#'], ' ')
                ->replaceMatches('/[^a-z0-9]+/u', '_')
                ->trim('_')
                ->value();

            return match ($normalized) {
                'question', 'question_text', 'text', 'vopros', 'tekst_voprosa' => 'question_text',
                'type', 'tip' => 'type',
                'points', 'point', 'score', 'ball', 'bally' => 'points',
                'correct', 'correct_answer', 'correct_answers', 'pravilnyj_otvet', 'pravilnye_otvety' => 'correct',
                'answer_a', 'a', 'variant_a', 'otvet_a' => 'answer_a',
                'answer_b', 'b', 'variant_b', 'otvet_b' => 'answer_b',
                'answer_c', 'c', 'variant_c', 'otvet_c' => 'answer_c',
                'answer_d', 'd', 'variant_d', 'otvet_d' => 'answer_d',
                'answer_e', 'e', 'variant_e', 'otvet_e' => 'answer_e',
                default => null,
            };
        }, $headers);
    }

    private function correctTokens(mixed $value): array
    {
        $values = is_array($value) ? $value : preg_split('/[\s,;|\/]+/u', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

        return collect($values)
            ->map(function ($token) {
                $trimmed = Str::upper(trim((string) $token));
                if ($trimmed === '') {
                    return null;
                }

                if (ctype_digit($trimmed)) {
                    $index = (int) $trimmed - 1;

                    return BlankScanLayout::answerLetters()[$index] ?? null;
                }

                return $trimmed;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->nullableString($value) !== null) {
                return false;
            }
        }

        return true;
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
