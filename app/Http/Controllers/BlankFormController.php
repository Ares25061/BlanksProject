<?php
namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\BlankForm;
use App\Services\BlankFormService;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BlankFormController extends Controller
{
    use AuthorizesRequests;

    protected $blankFormService;
    protected $gradingService;

    public function __construct(BlankFormService $blankFormService, GradingService $gradingService)
    {
        $this->blankFormService = $blankFormService;
        $this->gradingService = $gradingService;
    }

    public function generateForTest(Request $request, Test $test)
    {
        $this->authorize('generateBlankForms', $test);

        $validated = $request->validate([
            'count' => 'nullable|integer|min:1|max:100',
            'students' => 'nullable|array',
            'students.*.last_name' => 'nullable|string',
            'students.*.first_name' => 'nullable|string',
            'students.*.group_name' => 'nullable|string'
        ]);

        $count = $validated['count'] ?? 1;

        if (isset($validated['students'])) {
            $forms = [];
            foreach ($validated['students'] as $studentData) {
                $forms[] = $this->blankFormService->generateBlankForm($test, $studentData);
            }
        } else {
            $forms = $this->blankFormService->generateMultipleBlankForms($test, $count);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Бланки успешно сгенерированы',
            'data' => $forms
        ]);
    }

    public function show(BlankForm $blankForm)
    {
        $this->authorize('view', $blankForm);

        $blankForm->load(['test.questions.answers', 'studentAnswers']);

        return response()->json([
            'status' => 'success',
            'data' => $blankForm
        ]);
    }

    public function submitAnswers(Request $request, BlankForm $blankForm)
    {
        $this->authorize('submit', $blankForm);

        $validated = $request->validate([
            'last_name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'group_name' => 'nullable|string|max:255',
            'submission_date' => 'nullable|date',
            'questions' => 'required|array',
            'questions.*' => 'required' // ID ответа или массив ID для множественного выбора
        ]);

        $blankForm = $this->blankFormService->submitStudentAnswers($blankForm, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Ответы успешно сохранены',
            'data' => $blankForm
        ]);
    }

    public function check(BlankForm $blankForm)
    {
        $this->authorize('check', $blankForm);

        $blankForm = $this->gradingService->checkBlankForm($blankForm);

        return response()->json([
            'status' => 'success',
            'message' => 'Бланк успешно проверен',
            'data' => $blankForm
        ]);
    }

    public function checkMultiple(Request $request)
    {
        $this->authorize('checkMultiple', BlankForm::class);

        $validated = $request->validate([
            'blank_form_ids' => 'required|array',
            'blank_form_ids.*' => 'exists:blank_forms,id'
        ]);

        $results = $this->gradingService->checkMultipleBlankForms($validated['blank_form_ids']);

        return response()->json([
            'status' => 'success',
            'message' => 'Бланки успешно проверены',
            'data' => $results
        ]);
    }

    public function getGrade(BlankForm $blankForm)
    {
        $this->authorize('view', $blankForm);

        $grade = $this->gradingService->getStudentGrade($blankForm);

        return response()->json([
            'status' => 'success',
            'data' => $grade
        ]);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', BlankForm::class);

        $blankForms = BlankForm::with(['test', 'studentAnswers'])
            ->when($request->test_id, function ($query, $testId) {
                $query->where('test_id', $testId);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->group, function ($query, $group) {
                $query->where('group_name', 'like', "%{$group}%");
            })
            ->when($request->student, function ($query, $student) {
                $query->where(function($q) use ($student) {
                    $q->where('last_name', 'like', "%{$student}%")
                        ->orWhere('first_name', 'like', "%{$student}%");
                });
            })
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => 'success',
            'data' => $blankForms
        ]);
    }
}
