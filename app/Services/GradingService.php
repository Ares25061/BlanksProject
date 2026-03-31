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
        private TestVariantService $testVariantService,
    ) {
    }

    public function checkBlankForm(BlankForm $blankForm)
    {
        return DB::transaction(function () use ($blankForm) {
            $totalScore = 0;
            $blankForm->loadMissing('test.questions.answers', 'studentAnswers');
            $questions = $this->testVariantService
                ->questionsForVariant($blankForm->test, $blankForm->variant_number ?? 1)
                ->values();

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
        $questions = $this->testVariantService
            ->questionsForVariant($blankForm->test, $blankForm->variant_number ?? 1)
            ->values();
        $maxScore = $questions->sum('points');
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

    public function buildTransientScanReview(BlankForm $blankForm, array $questionAnswers, array $scanMetadata = []): array
    {
        $blankForm->loadMissing('test.questions.answers', 'studentGroup', 'groupStudent');
        $this->testVariantService->attachVariantAnswers($blankForm);
        $questions = $blankForm->test->questions->values();

        $studentAnswers = [];
        $totalScore = 0;

        foreach ($questions as $question) {
            $selectedAnswerIds = collect($questionAnswers[$question->id] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if ($question->type === 'single') {
                $correctAnswer = $question->answers()->where('is_correct', true)->first();
                $answerId = count($selectedAnswerIds) === 1 ? $selectedAnswerIds[0] : null;
                $isCorrect = $correctAnswer
                    && $answerId === (int) $correctAnswer->id
                    && count($selectedAnswerIds) <= 1;

                $studentAnswers[] = [
                    'question_id' => $question->id,
                    'answer_id' => $answerId,
                    'selected_answers' => count($selectedAnswerIds) > 1 ? $selectedAnswerIds : null,
                    'is_correct' => (bool) $isCorrect,
                    'points_earned' => $isCorrect ? (int) $question->points : 0,
                ];

                $totalScore += $isCorrect ? (int) $question->points : 0;
                continue;
            }

            $correctAnswers = $question->answers()->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $isCorrect = empty(array_diff($correctAnswers, $selectedAnswerIds))
                && empty(array_diff($selectedAnswerIds, $correctAnswers));

            $studentAnswers[] = [
                'question_id' => $question->id,
                'answer_id' => null,
                'selected_answers' => $selectedAnswerIds,
                'is_correct' => $isCorrect,
                'points_earned' => $isCorrect ? (int) $question->points : 0,
            ];

            $totalScore += $isCorrect ? (int) $question->points : 0;
        }

        $maxScore = (int) $questions->sum('points');
        $gradeLabel = $this->calculateGrade($blankForm->test, $totalScore, $maxScore);
        $data = $blankForm->toArray();
        $data['id'] = null;
        $data['original_blank_form_id'] = $blankForm->id;
        $data['status'] = 'foreign_preview';
        $data['student_answers'] = $studentAnswers;
        $data['total_score'] = $totalScore;
        $data['grade_label'] = $gradeLabel;
        $data['assigned_grade_value'] = null;
        $data['assigned_grade_date'] = null;
        $data['grade_assigner'] = null;
        $data['group_student_id'] = null;
        $data['can_assign_grade'] = false;
        $data['is_foreign_scan'] = true;
        $data['metadata'] = array_merge($data['metadata'] ?? [], [
            'scan' => $scanMetadata,
        ]);

        return [
            'data' => $data,
            'grade' => [
                'student_name' => $blankForm->student_full_name,
                'group' => $blankForm->group_name,
                'score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0,
                'grade' => $gradeLabel,
            ],
        ];
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
