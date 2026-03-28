<?php

namespace App\Services;

use App\Models\BlankForm;
use App\Models\StudentGrade;
use App\Models\GroupStudent;
use App\Models\StudentGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentGradeService
{
    public function buildGradebook(StudentGroup $group, ?string $subjectName = null): array
    {
        $group->loadMissing('students');

        $availableSubjects = $this->availableSubjects($group);
        $resolvedSubject = $this->resolveSubject($subjectName, $availableSubjects);
        $entries = $group->studentGrades()
            ->when($resolvedSubject !== '', fn ($query) => $query->where('subject_name', $resolvedSubject))
            ->orderBy('grade_date')
            ->get();

        $dates = $entries
            ->pluck('grade_date')
            ->filter()
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->unique()
            ->values();

        $students = $group->students->map(function ($student) use ($entries) {
            $gradeMap = $entries
                ->where('group_student_id', $student->id)
                ->mapWithKeys(fn (StudentGrade $entry) => [
                    $entry->grade_date?->format('Y-m-d') => [
                        'id' => $entry->id,
                        'grade_value' => $entry->grade_value,
                        'grade_date' => $entry->grade_date?->format('Y-m-d'),
                        'blank_form_id' => $entry->blank_form_id,
                        'subject_name' => $entry->subject_name,
                    ],
                ]);

            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'grades' => $gradeMap->all(),
            ];
        })->values();

        return [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
            ],
            'subject_name' => $resolvedSubject,
            'available_subjects' => $availableSubjects->values()->all(),
            'dates' => $dates->all(),
            'students' => $students->all(),
        ];
    }

    public function upsertManualEntry(StudentGroup $group, array $data): ?StudentGrade
    {
        return DB::transaction(function () use ($group, $data) {
            $student = $group->students()
                ->whereKey((int) $data['group_student_id'])
                ->first();

            if (!$student instanceof GroupStudent) {
                throw ValidationException::withMessages([
                    'group_student_id' => 'Студент не найден в выбранной группе.',
                ]);
            }

            $subjectName = trim((string) ($data['subject_name'] ?? ''));
            if ($subjectName === '') {
                throw ValidationException::withMessages([
                    'subject_name' => 'Укажите предмет для записи в журнал.',
                ]);
            }

            $entry = StudentGrade::firstOrNew([
                'group_student_id' => $student->id,
                'subject_name' => $subjectName,
                'grade_date' => $data['grade_date'],
            ]);

            if (!$entry->exists) {
                $entry->student_group_id = $group->id;
                $entry->created_by = Auth::id();
            }

            $gradeValue = trim((string) ($data['grade_value'] ?? ''));

            if ($gradeValue === '') {
                if ($entry->exists) {
                    $this->clearLinkedBlankFormGrade($entry);
                    $entry->delete();
                }

                return null;
            }

            $entry->fill([
                'student_group_id' => $group->id,
                'group_student_id' => $student->id,
                'subject_name' => $subjectName,
                'grade_date' => $data['grade_date'],
                'grade_value' => $gradeValue,
                'updated_by' => Auth::id(),
            ]);
            $entry->save();

            $this->syncLinkedBlankFormGrade($entry);

            return $entry->fresh(['blankForm', 'groupStudent']);
        });
    }

    public function syncFromBlankForm(BlankForm $blankForm): ?StudentGrade
    {
        if (!$blankForm->group_student_id || !$blankForm->student_group_id) {
            return null;
        }

        $gradeValue = trim((string) ($blankForm->assigned_grade_value ?? ''));
        if ($gradeValue === '' || !$blankForm->assigned_grade_date) {
            StudentGrade::where('blank_form_id', $blankForm->id)->delete();

            return null;
        }

        return DB::transaction(function () use ($blankForm, $gradeValue) {
            $subjectName = $blankForm->test?->subject_display_name ?: $blankForm->test?->title ?: 'Без предмета';

            $entry = StudentGrade::firstOrNew([
                'group_student_id' => $blankForm->group_student_id,
                'subject_name' => $subjectName,
                'grade_date' => $blankForm->assigned_grade_date->format('Y-m-d'),
            ]);

            $entry->student_group_id = $blankForm->student_group_id;
            $entry->blank_form_id = $blankForm->id;
            $entry->grade_value = $gradeValue;
            $entry->created_by = $entry->created_by ?: Auth::id();
            $entry->updated_by = Auth::id();
            $entry->save();

            StudentGrade::query()
                ->where('blank_form_id', $blankForm->id)
                ->where('id', '!=', $entry->id)
                ->delete();

            return $entry->fresh(['blankForm', 'groupStudent']);
        });
    }

    private function availableSubjects(StudentGroup $group): Collection
    {
        $fromTests = $group->blankForms()
            ->with('test')
            ->get()
            ->map(fn ($blankForm) => $blankForm->test?->subject_display_name)
            ->filter();

        $fromGrades = $group->studentGrades()
            ->pluck('subject_name')
            ->filter();

        return $fromGrades
            ->merge($fromTests)
            ->map(fn ($subject) => trim((string) $subject))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function resolveSubject(?string $subjectName, Collection $availableSubjects): string
    {
        $requested = trim((string) $subjectName);
        if ($requested !== '') {
            return $requested;
        }

        return (string) ($availableSubjects->first() ?? '');
    }

    private function syncLinkedBlankFormGrade(StudentGrade $entry): void
    {
        if (!$entry->blank_form_id) {
            return;
        }

        $blankForm = $entry->blankForm;
        if (!$blankForm) {
            return;
        }

        $blankForm->update([
            'assigned_grade_value' => $entry->grade_value,
            'assigned_grade_date' => $entry->grade_date,
            'assigned_grade_by' => Auth::id(),
        ]);
    }

    private function clearLinkedBlankFormGrade(StudentGrade $entry): void
    {
        if (!$entry->blank_form_id) {
            return;
        }

        $blankForm = $entry->blankForm;
        if (!$blankForm) {
            return;
        }

        $blankForm->update([
            'assigned_grade_value' => null,
            'assigned_grade_date' => null,
            'assigned_grade_by' => null,
        ]);
    }
}
