<?php
namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\BlankForm;
use App\Models\StudentGroup;
use App\Services\BlankFormService;
use App\Services\BlankScanService;
use App\Services\GradingService;
use App\Services\TestVariantService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class BlankFormController extends Controller
{
    use AuthorizesRequests;

    protected $blankFormService;
    protected $gradingService;
    protected $blankScanService;
    protected $testVariantService;

    public function __construct(
        BlankFormService $blankFormService,
        GradingService $gradingService,
        BlankScanService $blankScanService,
        TestVariantService $testVariantService,
    )
    {
        $this->blankFormService = $blankFormService;
        $this->gradingService = $gradingService;
        $this->blankScanService = $blankScanService;
        $this->testVariantService = $testVariantService;
    }

    public function generateForTest(Request $request, Test $test)
    {
        $this->authorize('generateBlankForms', $test);

        if ((string) $test->test_status === 'closed') {
            abort(422, 'Тест закрыт. Для него больше нельзя выпускать новые бланки.');
        }

        $validated = $request->validate([
            'count' => 'nullable|integer|min:1|max:100',
            'student_group_id' => 'nullable|exists:student_groups,id',
            'group_student_ids' => 'nullable|array',
            'group_student_ids.*' => 'integer|exists:group_students,id',
            'students' => 'nullable|array',
            'students.*.full_name' => 'nullable|string|max:255',
            'students.*.last_name' => 'nullable|string',
            'students.*.first_name' => 'nullable|string',
            'students.*.patronymic' => 'nullable|string',
            'students.*.group_name' => 'nullable|string',
            'variant_assignment_mode' => 'nullable|in:same,balanced,custom',
            'variant_number' => 'nullable|integer|min:1|max:10',
            'variant_numbers' => 'nullable|array',
        ]);

        $count = $validated['count'] ?? 1;
        $variantOptions = [
            'mode' => $validated['variant_assignment_mode'] ?? 'same',
            'variant_number' => $validated['variant_number'] ?? 1,
            'variant_numbers' => collect($request->input('variant_numbers', []))
                ->mapWithKeys(fn ($variantNumber, $studentId) => [(int) $studentId => (int) $variantNumber])
                ->all(),
        ];

        if (!empty($validated['student_group_id'])) {
            $group = StudentGroup::with('students')->findOrFail($validated['student_group_id']);
            $this->authorize('view', $group);

            $forms = $this->blankFormService->generateBlankFormsForGroup(
                $test,
                $group,
                $validated['group_student_ids'] ?? [],
                $variantOptions
            );
        } elseif (isset($validated['students'])) {
            $forms = [];
            foreach ($validated['students'] as $studentData) {
                $forms[] = $this->blankFormService->generateBlankForm(
                    $test,
                    $studentData,
                    $variantOptions['variant_number'] ?? 1
                );
            }
        } else {
            $forms = $this->blankFormService->generateMultipleBlankForms($test, $count, $variantOptions);
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

        $blankForm->load(['test.questions.answers', 'studentAnswers.question.answers', 'studentGroup', 'groupStudent', 'gradeAssigner']);
        $this->testVariantService->attachVariantAnswers($blankForm);
        $blankForm->setAttribute('can_assign_grade', (bool) $blankForm->group_student_id);
        $blankForm->setAttribute('is_foreign_scan', false);

        return response()->json([
            'status' => 'success',
            'data' => $blankForm,
            'grade' => $this->gradingService->getStudentGrade($blankForm->loadMissing('test.questions'))
        ]);
    }

    public function submitAnswers(Request $request, BlankForm $blankForm)
    {
        $this->authorize('submit', $blankForm);

        $validated = $request->validate([
            'last_name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'patronymic' => 'nullable|string|max:255',
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

    public function scanForTest(Request $request, Test $test)
    {
        $this->authorize('update', $test);

        $validated = $request->validate([
            'scans' => 'required|array|min:1',
            'scans.*' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:15360',
        ]);

        $results = $this->blankScanService->scanUploadedForms($test->load('questions.answers'), $validated['scans']);

        return response()->json([
            'status' => 'success',
            'message' => 'Сканы обработаны',
            'data' => $results,
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

    public function assignGrade(Request $request, BlankForm $blankForm)
    {
        $this->authorize('assignGrade', $blankForm);

        $validated = $request->validate([
            'grade_value' => 'required|string|max:50',
            'grade_date' => 'required|date',
        ]);

        $blankForm = $this->gradingService->assignStudentGrade($blankForm, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Оценка ученику сохранена',
            'data' => $blankForm,
        ]);
    }

    public function destroy(BlankForm $blankForm)
    {
        $this->authorize('delete', $blankForm);

        $this->blankFormService->deleteBlankForm($blankForm);

        return response()->json([
            'status' => 'success',
            'message' => 'Бланк удален',
        ]);
    }

    public function destroyIssuedForTest(Test $test)
    {
        $this->authorize('update', $test);

        $deletedCount = $this->blankFormService->deleteIssuedBlankFormsForTest($test);

        return response()->json([
            'status' => 'success',
            'message' => $deletedCount > 0
                ? "Удалено выпущенных бланков: {$deletedCount}"
                : 'Для этого теста нет выпущенных бланков, доступных для удаления.',
            'data' => [
                'deleted_count' => $deletedCount,
            ],
        ]);
    }

    public function scanImage(Request $request, BlankForm $blankForm)
    {
        $this->authorize('view', $blankForm);

        $requestedPage = (int) $request->query('page', 1);
        $pagePath = $this->resolveScanPathForPage($blankForm, $requestedPage);

        if (!$pagePath || !Storage::disk('local')->exists($pagePath)) {
            abort(404, 'Скан бланка не найден.');
        }

        return Storage::disk('local')->response(
            $pagePath,
            basename($pagePath),
            [],
            'inline'
        );
    }

    protected function resolveScanPathForPage(BlankForm $blankForm, int $pageNumber): ?string
    {
        $normalizedPage = max(1, $pageNumber);
        $pages = collect(data_get($blankForm->metadata, 'scan.pages', []))
            ->filter(fn ($page) => !empty($page['scan_path']))
            ->values();

        if ($pages->isNotEmpty()) {
            $matchedPage = $pages->first(fn ($page) => (int) ($page['page_number'] ?? 0) === $normalizedPage);

            return $matchedPage['scan_path'] ?? ($pages->first()['scan_path'] ?? null);
        }

        return $blankForm->scan_path;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', BlankForm::class);

        $blankForms = BlankForm::with(['test', 'studentAnswers', 'studentGroup', 'groupStudent', 'gradeAssigner'])
            ->when($request->test_id, function ($query, $testId) {
                $query->where('test_id', $testId);
            })
            ->when($request->student_group_id, function ($query, $groupId) {
                $query->where('student_group_id', $groupId);
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
