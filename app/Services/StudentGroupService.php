<?php

namespace App\Services;

use App\Models\StudentGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentGroupService
{
    public function createGroup(array $data): StudentGroup
    {
        return DB::transaction(function () use ($data) {
            $group = StudentGroup::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->syncStudents($group, $data['students'] ?? []);

            return $group->load('students');
        });
    }

    public function updateGroup(StudentGroup $group, array $data): StudentGroup
    {
        return DB::transaction(function () use ($group, $data) {
            $group->update([
                'name' => $data['name'] ?? $group->name,
                'description' => $data['description'] ?? $group->description,
            ]);

            if (array_key_exists('students', $data)) {
                $this->syncStudents($group, $data['students']);
            }

            return $group->load('students');
        });
    }

    public function deleteGroup(StudentGroup $group): bool
    {
        return (bool) $group->delete();
    }

    protected function syncStudents(StudentGroup $group, array $students): void
    {
        $normalizedStudents = collect($students)
            ->map(function ($student, int $index) {
                $fullName = is_array($student)
                    ? trim((string) ($student['full_name'] ?? ''))
                    : trim((string) $student);

                return [
                    'full_name' => preg_replace('/\s+/', ' ', $fullName),
                    'sort_order' => $index,
                ];
            })
            ->filter(fn (array $student) => $student['full_name'] !== '')
            ->values();

        $existingStudents = $group->students()->get()
            ->groupBy('full_name')
            ->map(fn ($items) => $items->values());

        $retainedStudentIds = [];

        foreach ($normalizedStudents as $studentData) {
            $sameNameStudents = $existingStudents->get($studentData['full_name'], collect());
            $existingStudent = $sameNameStudents->shift();

            if ($existingStudent) {
                $existingStudent->update([
                    'sort_order' => $studentData['sort_order'],
                ]);

                $existingStudents->put($studentData['full_name'], $sameNameStudents);
                $retainedStudentIds[] = $existingStudent->id;
                continue;
            }

            $retainedStudentIds[] = $group->students()->create($studentData)->id;
        }

        $group->students()
            ->whereNotIn('id', $retainedStudentIds)
            ->delete();
    }
}
