<?php
namespace App\Services;

use App\Models\Test;
use App\Models\BlankForm;
use App\Models\StudentGrade;
use App\Models\StudentGroup;
use App\Models\StudentAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Support\StudentName;

class BlankFormService
{
    public function generateBlankForm(Test $test, array $studentData = [])
    {
        return DB::transaction(function () use ($test, $studentData) {
            $formNumber = $this->generateFormNumber($test);
            $parsedName = StudentName::parse($studentData['full_name'] ?? null);

            $blankForm = BlankForm::create([
                'test_id' => $test->id,
                'student_group_id' => $studentData['student_group_id'] ?? null,
                'group_student_id' => $studentData['group_student_id'] ?? null,
                'form_number' => $formNumber,
                'last_name' => $studentData['last_name'] ?? $parsedName['last_name'],
                'first_name' => $studentData['first_name'] ?? $parsedName['first_name'],
                'patronymic' => $studentData['patronymic'] ?? $parsedName['patronymic'],
                'group_name' => $studentData['group_name'] ?? null,
                'submission_date' => $studentData['submission_date'] ?? null,
                'status' => 'generated',
                'metadata' => [
                    'generated_at' => now(),
                    'generated_by' => auth()->id(),
                    'student_full_name' => $studentData['full_name'] ?? $parsedName['full_name'],
                ]
            ]);

            return $blankForm->load(['test.questions.answers', 'studentGroup', 'groupStudent']);
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

    public function generateBlankFormsForGroup(Test $test, StudentGroup $group, array $studentIds = []): array
    {
        $normalizedStudentIds = collect($studentIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $studentsQuery = $group->students();

        if ($normalizedStudentIds->isNotEmpty()) {
            $studentsQuery->whereIn('id', $normalizedStudentIds->all());
        }

        $students = $studentsQuery->get();

        if ($normalizedStudentIds->isNotEmpty() && $students->count() !== $normalizedStudentIds->count()) {
            throw ValidationException::withMessages([
                'group_student_ids' => 'Некоторые выбранные студенты не относятся к этой группе.',
            ]);
        }

        if ($students->isEmpty()) {
            throw ValidationException::withMessages([
                'student_group_id' => 'В выбранной группе нет студентов для генерации бланков.',
            ]);
        }

        $forms = [];

        foreach ($students as $student) {
            $forms[] = $this->generateBlankForm($test, [
                'full_name' => $student->full_name,
                'group_name' => $group->name,
                'student_group_id' => $group->id,
                'group_student_id' => $student->id,
            ]);
        }

        return $forms;
    }

    public function submitStudentAnswers(BlankForm $blankForm, array $answers)
    {
        return DB::transaction(function () use ($blankForm, $answers) {
            $blankForm->update([
                'last_name' => $answers['last_name'] ?? $blankForm->last_name,
                'first_name' => $answers['first_name'] ?? $blankForm->first_name,
                'patronymic' => $answers['patronymic'] ?? $blankForm->patronymic,
                'group_name' => $answers['group_name'] ?? $blankForm->group_name,
                'submission_date' => $answers['submission_date'] ?? now(),
                'status' => 'submitted'
            ]);

            $this->storeStudentAnswers($blankForm, $answers['questions']);

            return $blankForm->load('studentAnswers');
        });
    }

    public function replaceStudentAnswersFromScan(BlankForm $blankForm, array $answers, array $scanMetadata = []): BlankForm
    {
        return DB::transaction(function () use ($blankForm, $answers, $scanMetadata) {
            $existingScanPaths = $this->extractScanPaths($blankForm);
            $blankForm->studentAnswers()->delete();

            $metadata = $blankForm->metadata ?? [];
            $metadata['scan'] = array_merge($metadata['scan'] ?? [], $scanMetadata, [
                'processed_at' => now()->toIso8601String(),
            ]);

            $blankForm->update([
                'submission_date' => now(),
                'status' => 'submitted',
                'scan_path' => $scanMetadata['scan_path'] ?? $blankForm->scan_path,
                'scanned_at' => now(),
                'metadata' => $metadata,
            ]);

            $this->storeStudentAnswers($blankForm, $answers);

            $newScanPaths = array_values(array_filter([
                $scanMetadata['scan_path'] ?? null,
                ...collect($scanMetadata['pages'] ?? [])->pluck('scan_path')->filter()->all(),
            ]));
            $pathsToDelete = array_diff($existingScanPaths, $newScanPaths);

            if ($pathsToDelete !== []) {
                Storage::disk('local')->delete($pathsToDelete);
            }

            return $blankForm->fresh(['studentAnswers', 'test.questions.answers', 'studentGroup', 'groupStudent']);
        });
    }

    protected function storeStudentAnswers(BlankForm $blankForm, array $questions): void
    {
        foreach ($questions as $questionId => $answerData) {
            $this->saveStudentAnswer($blankForm, (int) $questionId, $answerData);
        }
    }

    protected function saveStudentAnswer(BlankForm $blankForm, int $questionId, $answerData)
    {
        $question = $blankForm->test->questions()->findOrFail($questionId);

        $payload = [
            'blank_form_id' => $blankForm->id,
            'question_id' => $questionId,
            'answer_id' => null,
            'selected_answers' => null,
            'is_correct' => false,
            'points_earned' => 0,
        ];

        if ($question->type === 'single') {
            $selectedAnswerIds = is_array($answerData)
                ? array_values(array_unique(array_map('intval', $answerData)))
                : [(int) $answerData];

            $selectedAnswerIds = array_values(array_filter($selectedAnswerIds));
            $selectedAnswer = count($selectedAnswerIds) === 1
                ? $question->answers()->find($selectedAnswerIds[0])
                : null;

            $payload['answer_id'] = $selectedAnswer?->id;
            $payload['selected_answers'] = count($selectedAnswerIds) > 1 ? $selectedAnswerIds : null;
            $payload['is_correct'] = $selectedAnswer ? (bool) $selectedAnswer->is_correct : false;
            $payload['points_earned'] = $payload['is_correct'] ? $question->points : 0;
        } else {
            $selectedAnswerIds = collect(is_array($answerData) ? $answerData : [])
                ->map(fn ($value) => (int) $value)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $correctAnswers = $question->answers()->where('is_correct', true)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $payload['selected_answers'] = $selectedAnswerIds;
            $payload['is_correct'] = empty(array_diff($correctAnswers, $selectedAnswerIds))
                && empty(array_diff($selectedAnswerIds, $correctAnswers));
            $payload['points_earned'] = $payload['is_correct'] ? $question->points : 0;
        }

        return StudentAnswer::create($payload);
    }

    protected function generateFormNumber(Test $test): string
    {
        do {
            $number = 'TEST-' . $test->id . '-' . Str::random(8) . '-' . time();
        } while (BlankForm::where('form_number', $number)->exists());

        return $number;
    }

    public function deleteBlankForm(BlankForm $blankForm): void
    {
        DB::transaction(function () use ($blankForm) {
            $scanPaths = $this->extractScanPaths($blankForm);

            StudentGrade::where('blank_form_id', $blankForm->id)->delete();
            $blankForm->studentAnswers()->delete();
            $blankForm->delete();

            if ($scanPaths !== []) {
                Storage::disk('local')->delete($scanPaths);
            }
        });
    }

    protected function extractScanPaths(BlankForm $blankForm): array
    {
        $paths = array_filter([
            $blankForm->scan_path,
            ...collect(data_get($blankForm->metadata, 'scan.pages', []))
                ->pluck('scan_path')
                ->filter()
                ->all(),
        ]);

        return array_values(array_unique($paths));
    }
}
