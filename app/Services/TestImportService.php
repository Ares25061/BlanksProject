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
    public function __construct(
        private TestVariantService $testVariantService,
    ) {
    }

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

        $derivedVariantCount = $this->deriveVariantCount($questionSource, Arr::get($payload, 'variant_count'));

        return [
            'title' => $this->nullableString(Arr::get($payload, 'title')),
            'subject_name' => $this->nullableString(Arr::get($payload, 'subject_name')),
            'description' => $this->nullableString(Arr::get($payload, 'description')),
            'time_limit' => $this->nullableInt(Arr::get($payload, 'time_limit')),
            'variant_count' => $derivedVariantCount,
            'delivery_mode' => $this->normalizeDeliveryMode(Arr::get($payload, 'delivery_mode')),
            'grade_criteria' => $this->normalizeGradeCriteria(Arr::get($payload, 'grade_criteria', [])),
            'questions' => $this->normalizeQuestions($questionSource, $derivedVariantCount ?? 1),
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

        [$metadata, $headerRow, $dataRows] = $this->extractSpreadsheetSections($rows);
        $headers = $this->normalizeHeaders($headerRow);
        $questions = [];

        foreach ($dataRows as $row) {
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

        $questions = $this->resequenceQuestions($questions);

        if ($questions === []) {
            throw ValidationException::withMessages([
                'questions' => 'В XLSX-файле не найдено заполненных строк с вопросами.',
            ]);
        }

        $variantCount = $this->deriveVariantCount($questions, $metadata['variant_count'] ?? null);

        return [
            'title' => $this->nullableString($metadata['title'] ?? null),
            'subject_name' => $this->nullableString($metadata['subject_name'] ?? null),
            'description' => $this->nullableString($metadata['description'] ?? null),
            'time_limit' => $this->nullableInt($metadata['time_limit'] ?? null),
            'variant_count' => $variantCount,
            'delivery_mode' => $this->normalizeDeliveryMode($metadata['delivery_mode'] ?? null),
            'grade_criteria' => $this->normalizeGradeCriteria($metadata['grade_criteria'] ?? []),
            'questions' => $this->normalizeSpreadsheetQuestionVariants($questions, $variantCount),
        ];
    }

    private function normalizeQuestions(array $questions, int $variantCount = 1): array
    {
        $normalized = [];

        foreach ($questions as $index => $question) {
            if (!is_array($question)) {
                throw ValidationException::withMessages([
                    'questions' => 'Каждый вопрос в файле должен быть объектом.',
                ]);
            }

            $normalized[] = $this->normalizeQuestion($question, $index, $variantCount);
        }

        return $this->resequenceQuestions($normalized);
    }

    private function normalizeQuestion(array $question, int $index, int $variantCount = 1): array
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
            'order' => $this->normalizeQuestionOrder($question['order'] ?? $question['position'] ?? null, $index),
            'variant_number' => $this->normalizeQuestionVariant($question['variant_number'] ?? $question['variant'] ?? null, $variantCount),
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

        if ($this->nullableString($rowData['answer_e'] ?? null) !== null) {
            throw ValidationException::withMessages([
                'file' => 'В XLSX-файле найден пятый вариант ответа. Сейчас поддерживается максимум 4 варианта ответа на вопрос.',
            ]);
        }

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
            'order' => $this->normalizeQuestionOrder($rowData['order'] ?? null, $index),
            'variant_number' => $this->normalizeQuestionVariant($rowData['variant'] ?? null, 10),
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
                'order', 'position', 'index', 'poryadok' => 'order',
                'variant', 'variant_number', 'вариант' => 'variant',
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

    private function extractSpreadsheetSections(array $rows): array
    {
        foreach (array_values($rows) as $index => $row) {
            $normalizedHeaders = $this->normalizeHeaders($row);

            if (in_array('question_text', $normalizedHeaders, true)) {
                return [
                    $this->parseSpreadsheetMetadata(array_slice($rows, 0, $index)),
                    $row,
                    array_slice($rows, $index + 1),
                ];
            }
        }

        throw ValidationException::withMessages([
            'file' => 'В XLSX-файле не найдена строка заголовков с колонкой question_text.',
        ]);
    }

    private function parseSpreadsheetMetadata(array $rows): array
    {
        $metadata = [];

        foreach ($rows as $row) {
            $key = $this->normalizeSpreadsheetMetadataKey($row[0] ?? null);
            if ($key === null) {
                continue;
            }

            $value = collect($row)
                ->slice(1)
                ->map(fn ($cell) => trim((string) $cell))
                ->filter(fn (string $cell) => $cell !== '')
                ->implode(' ');

            if ($value === '') {
                continue;
            }

            if ($key === 'grade_criteria') {
                $metadata[$key] = $this->decodeSpreadsheetGradeCriteria($value);
                continue;
            }

            $metadata[$key] = $value;
        }

        return $metadata;
    }

    private function normalizeSpreadsheetMetadataKey(mixed $value): ?string
    {
        $normalized = Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/u', '_')
            ->trim('_')
            ->value();

        return match ($normalized) {
            'title', 'name', 'nazvanie' => 'title',
            'subject_name', 'subject', 'predmet' => 'subject_name',
            'description', 'opisanie' => 'description',
            'time_limit', 'time', 'vremya', 'time_limit_minutes' => 'time_limit',
            'variant_count', 'variants', 'kolichestvo_variantov' => 'variant_count',
            'delivery_mode', 'format', 'mode', 'format_provedeniya' => 'delivery_mode',
            'grade_criteria_json', 'grade_criteria', 'criteria', 'kriterii' => 'grade_criteria',
            default => null,
        };
    }

    private function decodeSpreadsheetGradeCriteria(string $value): array
    {
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'grade_criteria' => 'Не удалось разобрать grade_criteria_json в XLSX. Ожидается JSON-массив.',
            ]);
        }

        if (!is_array($decoded)) {
            throw ValidationException::withMessages([
                'grade_criteria' => 'grade_criteria_json в XLSX должен быть массивом.',
            ]);
        }

        return $decoded;
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

    private function normalizeVariantCount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->testVariantService->normalizeVariantCount((int) $value);
    }

    private function deriveVariantCount(array $questions, mixed $explicitVariantCount): int
    {
        $normalizedExplicit = $this->normalizeVariantCount($explicitVariantCount);
        $derived = collect($questions)
            ->map(fn ($question) => is_array($question) ? (int) ($question['variant_number'] ?? $question['variant'] ?? 1) : 1)
            ->max() ?: 1;

        if ($normalizedExplicit !== null && $normalizedExplicit < $derived) {
            throw ValidationException::withMessages([
                'variant_count' => 'Поле variant_count меньше, чем максимальный номер варианта в вопросах.',
            ]);
        }

        return $normalizedExplicit ?? $this->testVariantService->normalizeVariantCount($derived);
    }

    private function normalizeSpreadsheetQuestionVariants(array $questions, int $variantCount): array
    {
        return array_map(function (array $question) use ($variantCount) {
            $question['variant_number'] = $this->normalizeQuestionVariant($question['variant_number'] ?? 1, $variantCount);

            return $question;
        }, $questions);
    }

    private function normalizeQuestionVariant(mixed $value, int $variantCount): int
    {
        if ($value === null || $value === '') {
            return 1;
        }

        $variantNumber = (int) $value;

        if ($variantNumber < 1) {
            throw ValidationException::withMessages([
                'questions' => 'Номер варианта вопроса должен быть не меньше 1.',
            ]);
        }

        if ($variantNumber > $variantCount) {
            throw ValidationException::withMessages([
                'questions' => 'Номер варианта вопроса не может быть больше ' . $variantCount . '.',
            ]);
        }

        return $this->testVariantService->normalizeVariantCount($variantNumber);
    }

    private function normalizeQuestionOrder(mixed $value, int $fallback): int
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        return max(0, (int) $value);
    }

    private function resequenceQuestions(array $questions): array
    {
        return collect($questions)
            ->values()
            ->map(function (array $question, int $sourceIndex) {
                $question['_source_index'] = $sourceIndex;

                return $question;
            })
            ->sortBy([
                fn (array $question) => (int) ($question['order'] ?? 0),
                fn (array $question) => (int) ($question['_source_index'] ?? 0),
            ])
            ->values()
            ->map(function (array $question, int $index) {
                unset($question['_source_index']);
                $question['order'] = $index;

                return $question;
            })
            ->all();
    }

    private function normalizeDeliveryMode(mixed $value): string
    {
        $normalized = Str::lower(trim((string) $value));

        return match ($normalized) {
            'electronic', 'electron', 'электронно', 'электронный' => 'electronic',
            'hybrid', 'mixed', 'combined', 'совмещенный', 'совмещённый' => 'hybrid',
            default => 'blank',
        };
    }
}
