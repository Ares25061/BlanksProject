<?php
namespace App\Services;

use App\Models\BlankForm;
use App\Models\StudentAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GradingService
{
    public function __construct(
        private StudentGradeService $studentGradeService,
    ) {
    }

    public function checkBlankForm(BlankForm $blankForm)
    {
        return DB::transaction(function () use ($blankForm) {
            $totalScore = 0;
            $blankForm->loadMissing('test.questions.answers', 'studentAnswers');
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
                'grade_label' => $this->calculateGrade($blankForm->test, $totalScore, (int) $questions->sum('points')),
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
            $selectedAnswers = collect($studentAnswer->selected_answers ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            $answerId = $studentAnswer->answer_id;

            if (!$answerId && count($selectedAnswers) === 1) {
                $answerId = $selectedAnswers[0];
            }

            $isCorrect = $correctAnswer
                && $answerId == $correctAnswer->id
                && count($selectedAnswers) <= 1;

            $studentAnswer->update([
                'answer_id' => $answerId,
                'selected_answers' => count($selectedAnswers) > 1 ? $selectedAnswers : null,
                'is_correct' => (bool) $isCorrect,
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
            'student_name' => $blankForm->student_full_name,
            'group' => $blankForm->group_name,
            'score' => $studentScore,
            'max_score' => $maxScore,
            'percentage' => $maxScore > 0 ? round(($studentScore / $maxScore) * 100, 2) : 0,
            'grade' => $blankForm->grade_label ?: $this->calculateGrade($blankForm->test, $studentScore, $maxScore)
        ];
    }

    public function assignStudentGrade(BlankForm $blankForm, array $data): BlankForm
    {
        if ($blankForm->status !== 'checked') {
            throw ValidationException::withMessages([
                'blank_form' => 'Оценку можно поставить только после проверки работы.',
            ]);
        }

        if (!$blankForm->group_student_id) {
            throw ValidationException::withMessages([
                'blank_form' => 'Этот бланк не привязан к студенту учебной группы.',
            ]);
        }

        $blankForm->update([
            'assigned_grade_value' => trim((string) $data['grade_value']),
            'assigned_grade_date' => $data['grade_date'],
            'assigned_grade_by' => auth()->id(),
        ]);

        $blankForm->loadMissing('test');
        $this->studentGradeService->syncFromBlankForm($blankForm);

        return $blankForm->fresh([
            'test.questions.answers',
            'studentAnswers.question.answers',
            'studentGroup',
            'groupStudent',
            'gradeAssigner',
        ]);
    }

    protected function calculateGrade($test, $score, $maxScore)
    {
        if ($maxScore == 0) {
            return 'N/A';
        }

        $criteria = collect($test->grade_criteria ?? [])
            ->sortByDesc('min_points')
            ->values();

        if ($criteria->isNotEmpty()) {
            $criterion = $criteria->first(fn ($item) => (int) $score >= (int) $item['min_points']);

            return $criterion['label'] ?? $criteria->last()['label'];
        }

        $percentage = ($score / $maxScore) * 100;

        return match (true) {
            $percentage >= 90 => '5 (Отлично)',
            $percentage >= 75 => '4 (Хорошо)',
            $percentage >= 60 => '3 (Удовлетворительно)',
            default => '2 (Неудовлетворительно)'
        };
    }
}
