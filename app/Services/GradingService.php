<?php
namespace App\Services;

use App\Models\BlankForm;
use App\Models\StudentAnswer;
use Illuminate\Support\Facades\DB;

class GradingService
{
    public function checkBlankForm(BlankForm $blankForm)
    {
        return DB::transaction(function () use ($blankForm) {
            $totalScore = 0;
            $questions = $blankForm->test->questions;

            foreach ($questions as $question) {
                $studentAnswer = $blankForm->studentAnswers()
                    ->where('question_id', $question->id)
                    ->first();

                if ($studentAnswer) {
                    $this->evaluateAnswer($studentAnswer, $question);
                    $totalScore += $studentAnswer->points_earned ?? 0;
                }
            }

            $blankForm->update([
                'total_score' => $totalScore,
                'status' => 'checked',
                'checked_by' => auth()->id(),
                'checked_at' => now()
            ]);

            return $blankForm->load('studentAnswers');
        });
    }

    public function checkMultipleBlankForms(array $blankFormIds)
    {
        $results = [];
        foreach ($blankFormIds as $id) {
            $blankForm = BlankForm::find($id);
            if ($blankForm) {
                $results[] = $this->checkBlankForm($blankForm);
            }
        }
        return $results;
    }

    protected function evaluateAnswer(StudentAnswer $studentAnswer, $question)
    {
        if ($question->type === 'single') {
            $correctAnswer = $question->answers()->where('is_correct', true)->first();
            $isCorrect = $correctAnswer && $studentAnswer->answer_id == $correctAnswer->id;
            $studentAnswer->update([
                'is_correct' => $isCorrect,
                'points_earned' => $isCorrect ? $question->points : 0
            ]);
        } else {
            $correctAnswers = $question->answers()->where('is_correct', true)->pluck('id')->toArray();
            $selectedAnswers = $studentAnswer->selected_answers ?? [];

            $isCorrect = !array_diff($correctAnswers, $selectedAnswers) &&
                !array_diff($selectedAnswers, $correctAnswers);

            $studentAnswer->update([
                'is_correct' => $isCorrect,
                'points_earned' => $isCorrect ? $question->points : 0
            ]);
        }
    }

    public function getStudentGrade(BlankForm $blankForm)
    {
        $maxScore = $blankForm->test->questions->sum('points');
        $studentScore = $blankForm->total_score ?? 0;

        return [
            'student_name' => $blankForm->last_name . ' ' . $blankForm->first_name,
            'group' => $blankForm->group_name,
            'score' => $studentScore,
            'max_score' => $maxScore,
            'percentage' => $maxScore > 0 ? round(($studentScore / $maxScore) * 100, 2) : 0,
            'grade' => $this->calculateGrade($studentScore, $maxScore)
        ];
    }

    protected function calculateGrade($score, $maxScore)
    {
        if ($maxScore == 0) return 'N/A';

        $percentage = ($score / $maxScore) * 100;

        return match(true) {
            $percentage >= 90 => '5 (Отлично)',
            $percentage >= 75 => '4 (Хорошо)',
            $percentage >= 60 => '3 (Удовлетворительно)',
            default => '2 (Неудовлетворительно)'
        };
    }
}
