<?php
namespace App\Services;

use App\Models\Test;
use App\Models\BlankForm;
use App\Models\StudentAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlankFormService
{
    public function generateBlankForm(Test $test, array $studentData = [])
    {
        return DB::transaction(function () use ($test, $studentData) {
            $formNumber = $this->generateFormNumber($test);

            $blankForm = BlankForm::create([
                'test_id' => $test->id,
                'form_number' => $formNumber,
                'last_name' => $studentData['last_name'] ?? null,
                'first_name' => $studentData['first_name'] ?? null,
                'group_name' => $studentData['group_name'] ?? null,
                'submission_date' => $studentData['submission_date'] ?? null,
                'status' => 'generated',
                'metadata' => [
                    'generated_at' => now(),
                    'generated_by' => auth()->id()
                ]
            ]);

            return $blankForm->load('test.questions.answers');
        });
    }

    public function generateMultipleBlankForms(Test $test, int $count)
    {
        $forms = [];
        for ($i = 0; $i < $count; $i++) {
            $forms[] = $this->generateBlankForm($test);
        }
        return $forms;
    }

    public function submitStudentAnswers(BlankForm $blankForm, array $answers)
    {
        return DB::transaction(function () use ($blankForm, $answers) {
            // Обновляем информацию о студенте
            $blankForm->update([
                'last_name' => $answers['last_name'] ?? $blankForm->last_name,
                'first_name' => $answers['first_name'] ?? $blankForm->first_name,
                'group_name' => $answers['group_name'] ?? $blankForm->group_name,
                'submission_date' => $answers['submission_date'] ?? now(),
                'status' => 'submitted'
            ]);

            // Сохраняем ответы на вопросы
            foreach ($answers['questions'] as $questionId => $answerData) {
                $this->saveStudentAnswer($blankForm, $questionId, $answerData);
            }

            return $blankForm->load('studentAnswers');
        });
    }

    protected function saveStudentAnswer(BlankForm $blankForm, int $questionId, $answerData)
    {
        $question = $blankForm->test->questions()->findOrFail($questionId);

        $studentAnswer = new StudentAnswer([
            'blank_form_id' => $blankForm->id,
            'question_id' => $questionId
        ]);

        if ($question->type === 'single') {
            $studentAnswer->answer_id = $answerData;
            $selectedAnswer = $question->answers()->find($answerData);
            $studentAnswer->is_correct = $selectedAnswer ? $selectedAnswer->is_correct : false;
            $studentAnswer->points_earned = $studentAnswer->is_correct ? $question->points : 0;
        } else {
            // Множественный выбор
            $studentAnswer->selected_answers = $answerData;
            $correctAnswers = $question->answers()->where('is_correct', true)->pluck('id')->toArray();
            $studentAnswer->is_correct = empty(array_diff($correctAnswers, $answerData)) &&
                empty(array_diff($answerData, $correctAnswers));
            $studentAnswer->points_earned = $studentAnswer->is_correct ? $question->points : 0;
        }

        $studentAnswer->save();
        return $studentAnswer;
    }

    protected function generateFormNumber(Test $test): string
    {
        do {
            $number = 'TEST-' . $test->id . '-' . Str::random(8) . '-' . time();
        } while (BlankForm::where('form_number', $number)->exists());

        return $number;
    }
}
