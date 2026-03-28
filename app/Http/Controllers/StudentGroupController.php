<?php

namespace App\Http\Controllers;

use App\Models\StudentGroup;
use App\Services\StudentGradeService;
use App\Services\StudentGroupService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StudentGroupController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private StudentGroupService $studentGroupService,
        private StudentGradeService $studentGradeService,
    )
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', StudentGroup::class);

        $groups = StudentGroup::with('students')
            ->with('students.gradebookEntries')
            ->where('created_by', $request->user()->id)
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $groups,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', StudentGroup::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'students' => 'required|array|min:1',
            'students.*' => 'required|string|max:255',
        ]);

        $group = $this->studentGroupService->createGroup($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Группа успешно создана',
            'data' => $group,
        ], 201);
    }

    public function show(StudentGroup $studentGroup)
    {
        $this->authorize('view', $studentGroup);

        return response()->json([
            'status' => 'success',
            'data' => $studentGroup->load('students.gradebookEntries'),
        ]);
    }

    public function update(Request $request, StudentGroup $studentGroup)
    {
        $this->authorize('update', $studentGroup);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'students' => 'required|array|min:1',
            'students.*' => 'required|string|max:255',
        ]);

        $group = $this->studentGroupService->updateGroup($studentGroup, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Группа успешно обновлена',
            'data' => $group,
        ]);
    }

    public function destroy(StudentGroup $studentGroup)
    {
        $this->authorize('delete', $studentGroup);

        $this->studentGroupService->deleteGroup($studentGroup);

        return response()->json([
            'status' => 'success',
            'message' => 'Группа успешно удалена',
        ]);
    }

    public function gradebook(Request $request, StudentGroup $studentGroup)
    {
        $this->authorize('view', $studentGroup);

        $validated = $request->validate([
            'subject_name' => 'nullable|string|max:255',
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $this->studentGradeService->buildGradebook(
                $studentGroup,
                $validated['subject_name'] ?? null,
            ),
        ]);
    }

    public function upsertGradebookEntry(Request $request, StudentGroup $studentGroup)
    {
        $this->authorize('update', $studentGroup);

        $validated = $request->validate([
            'group_student_id' => 'required|integer|exists:group_students,id',
            'subject_name' => 'required|string|max:255',
            'grade_date' => 'required|date',
            'grade_value' => 'nullable|string|max:50',
        ]);

        $entry = $this->studentGradeService->upsertManualEntry($studentGroup, $validated);

        return response()->json([
            'status' => 'success',
            'message' => $entry ? 'Оценка сохранена' : 'Оценка удалена',
            'data' => $entry,
        ]);
    }
}
