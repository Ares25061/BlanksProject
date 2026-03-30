<?php
// app/Services/TestService.php
namespace App\Services;

use App\Models\Test;
use App\Models\Question;
use App\Models\Answer;
use App\Support\BlankScanLayout;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TestService
{
    public function createTest(array $data)
    {
        return DB::transaction(function () use ($data) {
            $this->ensureQuestionStructureWithinScanFormat($data['questions'] ?? []);
            $subjectName = $this->normalizeSubjectName($data['subject_name'] ?? null, $data['title']);

            $test = Test::create([
                'title' => $data['title'],
                'subject_name' => $subjectName,
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
                'time_limit' => $data['time_limit'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'grade_criteria' => $this->normalizeGradeCriteria($data['grade_criteria'] ?? []),
            ]);

            if (isset($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    $this->addQuestionToTest($test, $questionData);
                }
            }

            $this->ensureGradeCriteriaFitsMaxScore($test->fresh('questions'));

            return $test->load('questions.answers');
        });
    }

    public function updateTest(Test $test, array $data)
    {
        return DB::transaction(function () use ($test, $data) {
            if (isset($data['questions'])) {
                $this->ensureQuestionStructureWithinScanFormat($data['questions']);
            }

            $nextTitle = $data['title'] ?? $test->title;

            // Обновляем основную информацию теста
            $test->update([
                'title' => $nextTitle,
                'subject_name' => $this->normalizeSubjectName($data['subject_name'] ?? $test->subject_name, $nextTitle),
                'description' => $data['description'] ?? $test->description,
                'time_limit' => $data['time_limit'] ?? $test->time_limit,
                'is_active' => $data['is_active'] ?? $test->is_active,
                'grade_criteria' => $this->normalizeGradeCriteria($data['grade_criteria'] ?? $test->grade_criteria ?? []),
            ]);

            // Обновляем вопросы, если они переданы
            if (isset($data['questions'])) {
                $this->updateTestQuestions($test, $data['questions']);
            }

            $this->ensureGradeCriteriaFitsMaxScore($test->fresh('questions'));

            return $test->load('questions.answers');
        });
    }

    protected function updateTestQuestions(Test $test, array $questions)
    {
        // Получаем ID существующих вопросов
        $existingQuestionIds = $test->questions()->pluck('id')->toArray();
        $updatedQuestionIds = [];

        foreach ($questions as $index => $questionData) {
            if (isset($questionData['id']) && in_array($questionData['id'], $existingQuestionIds)) {
                // Обновляем существующий вопрос
                $question = Question::find($questionData['id']);
                $question->update([
                    'question_text' => $questionData['question_text'],
                    'type' => $questionData['type'],
                    'points' => $questionData['points'] ?? 1,
                    'order' => $index
                ]);

                // Обновляем ответы
                $this->updateQuestionAnswers($question, $questionData['answers'] ?? []);

                $updatedQuestionIds[] = $question->id;
            } else {
                // Создаем новый вопрос
                $question = $test->questions()->create([
                    'question_text' => $questionData['question_text'],
                    'type' => $questionData['type'],
                    'points' => $questionData['points'] ?? 1,
                    'order' => $index
                ]);

                // Добавляем ответы
                if (isset($questionData['answers'])) {
                    foreach ($questionData['answers'] as $answerData) {
                        $question->answers()->create([
                            'answer_text' => $answerData['answer_text'],
                            'is_correct' => $answerData['is_correct'] ?? false,
                            'order' => $answerData['order'] ?? 0
                        ]);
                    }
                }

                $updatedQuestionIds[] = $question->id;
            }
        }

        // Удаляем вопросы, которых нет в обновленном списке
        $questionsToDelete = array_diff($existingQuestionIds, $updatedQuestionIds);
        if (!empty($questionsToDelete)) {
            Question::whereIn('id', $questionsToDelete)->delete();
        }
    }

    protected function updateQuestionAnswers(Question $question, array $answers)
    {
        // Получаем ID существующих ответов
        $existingAnswerIds = $question->answers()->pluck('id')->toArray();
        $updatedAnswerIds = [];

        foreach ($answers as $index => $answerData) {
            if (isset($answerData['id']) && in_array($answerData['id'], $existingAnswerIds)) {
                // Обновляем существующий ответ
                $answer = Answer::find($answerData['id']);
                $answer->update([
                    'answer_text' => $answerData['answer_text'],
                    'is_correct' => $answerData['is_correct'] ?? false,
                    'order' => $index
                ]);
                $updatedAnswerIds[] = $answer->id;
            } else {
                // Создаем новый ответ
                $answer = $question->answers()->create([
                    'answer_text' => $answerData['answer_text'],
                    'is_correct' => $answerData['is_correct'] ?? false,
                    'order' => $index
                ]);
                $updatedAnswerIds[] = $answer->id;
            }
        }

        // Удаляем ответы, которых нет в обновленном списке
        $answersToDelete = array_diff($existingAnswerIds, $updatedAnswerIds);
        if (!empty($answersToDelete)) {
            Answer::whereIn('id', $answersToDelete)->delete();
        }
    }

    public function addQuestionToTest(Test $test, array $questionData)
    {
        $this->ensureAnswerCountWithinScanLimit($questionData['answers'] ?? []);

        $question = $test->questions()->create([
            'question_text' => $questionData['question_text'],
            'type' => $questionData['type'],
            'points' => $questionData['points'] ?? 1,
            'order' => $questionData['order'] ?? ($test->questions()->max('order') + 1)
        ]);

        if (isset($questionData['answers'])) {
            foreach ($questionData['answers'] as $index => $answerData) {
                $question->answers()->create([
                    'answer_text' => $answerData['answer_text'],
                    'is_correct' => $answerData['is_correct'] ?? false,
                    'order' => $index
                ]);
            }
        }

        return $question->load('answers');
    }

    public function deleteTest(Test $test)
    {
        return $test->delete();
    }

    protected function normalizeGradeCriteria(array $gradeCriteria): array
    {
        return collect($gradeCriteria)
            ->map(function (array $criterion) {
                return [
                    'label' => trim((string) ($criterion['label'] ?? '')),
                    'min_points' => (int) ($criterion['min_points'] ?? 0),
                ];
            })
            ->filter(fn (array $criterion) => $criterion['label'] !== '')
            ->sortByDesc('min_points')
            ->values()
            ->all();
    }

    protected function ensureGradeCriteriaFitsMaxScore(Test $test): void
    {
        $maxScore = (int) $test->questions->sum('points');
        $invalidCriterion = collect($test->grade_criteria ?? [])
            ->first(fn (array $criterion) => (int) $criterion['min_points'] > $maxScore);

        if ($invalidCriterion) {
            throw ValidationException::withMessages([
                'grade_criteria' => "Критерий \"{$invalidCriterion['label']}\" превышает максимальный балл теста ({$maxScore}).",
            ]);
        }
    }

    protected function ensureQuestionStructureWithinScanFormat(array $questions): void
    {
        foreach ($questions as $index => $question) {
            $this->ensureAnswerCountWithinScanLimit($question['answers'] ?? [], $index);
        }
    }

    protected function ensureAnswerCountWithinScanLimit(array $answers, ?int $questionIndex = null): void
    {
        if (count($answers) <= BlankScanLayout::ANSWER_OPTION_COUNT) {
            return;
        }

        $key = $questionIndex === null
            ? 'answers'
            : 'questions.' . $questionIndex . '.answers';

        $message = $questionIndex === null
            ? 'Для одного вопроса доступно не более ' . BlankScanLayout::ANSWER_OPTION_COUNT . ' вариантов ответа.'
            : 'В вопросе ' . ($questionIndex + 1) . ' больше ' . BlankScanLayout::ANSWER_OPTION_COUNT . ' вариантов ответа.';

        throw ValidationException::withMessages([
            $key => $message,
        ]);
    }

    protected function normalizeSubjectName(?string $subjectName, string $fallbackTitle): string
    {
        $normalized = trim((string) $subjectName);

        return $normalized !== '' ? $normalized : trim($fallbackTitle);
    }
}
