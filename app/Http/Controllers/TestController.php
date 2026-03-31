<?php
namespace App\Http\Controllers;

use App\Models\BlankForm;
use App\Models\Test;
use App\Services\TestExportService;
use App\Services\TestService;
use App\Services\TestImportService;
use App\Services\TestPrintLayoutService;
use App\Services\TestVariantService;
use App\Support\BlankScanLayout;
use App\Http\Requests\TestRequest;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;

class TestController extends Controller
{
    use AuthorizesRequests;

    protected $testService;
    protected $testImportService;
    protected $testExportService;
    protected $testPrintLayoutService;
    protected $testVariantService;

    public function __construct(
        TestService $testService,
        TestImportService $testImportService,
        TestExportService $testExportService,
        TestPrintLayoutService $testPrintLayoutService,
        TestVariantService $testVariantService,
    )
    {
        $this->testService = $testService;
        $this->testImportService = $testImportService;
        $this->testExportService = $testExportService;
        $this->testPrintLayoutService = $testPrintLayoutService;
        $this->testVariantService = $testVariantService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Test::class);

        $tests = Test::with(['creator', 'questions.answers'])
            ->where('created_by', $request->user()->id)
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => 'success',
            'data' => $tests
        ]);
    }

    public function store(TestRequest $request)
    {
        $this->authorize('create', Test::class);

        $test = $this->testService->createTest($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Тест успешно создан',
            'data' => $test
        ], 201);
    }

    public function show(Test $test)
    {
        $this->authorize('view', $test);

        $test->load(['creator', 'questions.answers']);

        return response()->json([
            'status' => 'success',
            'data' => $test
        ]);
    }

    public function update(TestRequest $request, Test $test)
    {
        $this->authorize('update', $test);

        $test = $this->testService->updateTest($test, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Тест успешно обновлен',
            'data' => $test
        ]);
    }

    public function destroy(Test $test)
    {
        $this->authorize('delete', $test);

        $this->testService->deleteTest($test);

        return response()->json([
            'status' => 'success',
            'message' => 'Тест успешно удален'
        ]);
    }

    public function addQuestion(Request $request, Test $test)
    {
        $this->authorize('update', $test);

        $validated = $request->validate([
            'question_text' => 'required|string',
            'type' => 'required|in:single,multiple',
            'points' => 'nullable|integer|min:1',
            'order' => 'nullable|integer',
            'variant_number' => 'nullable|integer|min:1|max:10',
            'answers' => 'required|array|min:2|max:' . BlankScanLayout::ANSWER_OPTION_COUNT,
            'answers.*.answer_text' => 'required|string',
            'answers.*.is_correct' => 'boolean',
            'answers.*.order' => 'nullable|integer'
        ]);

        $question = $this->testService->addQuestionToTest($test, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Вопрос успешно добавлен',
            'data' => $question
        ]);
    }

    public function importQuestions(Request $request)
    {
        $this->authorize('create', Test::class);

        $validated = $request->validate([
            'file' => 'required|file|mimes:json,xlsx|max:5120',
        ]);

        $imported = $this->testImportService->importFromUploadedFile($validated['file']);

        return response()->json([
            'status' => 'success',
            'message' => 'Вопросы успешно импортированы',
            'data' => $imported,
        ]);
    }

    public function export(Request $request, Test $test)
    {
        $this->authorize('view', $test);

        $format = Str::lower(trim((string) $request->query('format', 'json')));
        $loadedTest = $test->load('questions.answers');

        if ($format === 'xlsx' || $format === 'excel') {
            $path = $this->testExportService->buildSpreadsheetPath($loadedTest);
            $fileName = $this->testExportService->buildDownloadFileName($loadedTest, 'xlsx');

            return response()->download(
                $path,
                $fileName,
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(true);
        }

        if ($format !== 'json') {
            abort(422, 'Поддерживаются только форматы json и xlsx.');
        }

        $payload = $this->testExportService->buildJsonPayload($loadedTest);
        $fileName = $this->testExportService->buildDownloadFileName($loadedTest, 'json');

        return response()->json(
            $payload,
            200,
            ['Content-Disposition' => 'attachment; filename="' . $fileName . '"'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    public function print(Request $request, Test $test)
    {
        // Web-страницы в этом проекте сейчас открываются без Laravel session:
        // авторизация преподавателя живет в JWT внутри localStorage и используется API-запросами.
        // Поэтому для печатной страницы не выполняем обязательную policy-проверку по session-user,
        // иначе маршрут стабильно отдает 403 даже для авторизованного преподавателя.
        if ($request->user()) {
            $this->authorize('view', $test);
        }

        $blankFormIds = collect(explode(',', (string) $request->query('blank_form_ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values();

        $blankForms = BlankForm::with(['test.questions.answers', 'studentGroup', 'groupStudent'])
            ->where('test_id', $test->id)
            ->when($blankFormIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $blankFormIds))
            ->orderBy('group_name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($blankForms->isEmpty()) {
            $blankForms = collect([
                new BlankForm([
                    'id' => 0,
                    'test_id' => $test->id,
                    'form_number' => 'PREVIEW',
                    'variant_number' => (int) $request->query('variant_number', 1),
                    'group_name' => 'ДЕМО-ГРУППА',
                    'last_name' => 'ИВАНОВ',
                    'first_name' => 'ИВАН',
                    'patronymic' => 'ИВАНОВИЧ',
                    'metadata' => ['is_preview' => true],
                ]),
            ]);
        }

        $printMode = $this->normalizePrintMode((string) $request->query('print_mode', 'all'));
        $documentTitle = $this->buildPrintDocumentTitle($test, $blankForms, $printMode);

        $loadedTest = $test->load('questions.answers');
        $answerSheetPagesByBlankForm = $blankForms->mapWithKeys(function (BlankForm $blankForm) use ($loadedTest) {
            $variantNumber = $this->testVariantService->normalizeVariantNumber($loadedTest, $blankForm->variant_number ?? 1);

            return [
                (int) $blankForm->id => $this->testPrintLayoutService->paginateAnswerSheetQuestions($loadedTest, $variantNumber),
            ];
        })->all();
        $questionPagesByBlankForm = $blankForms->mapWithKeys(function (BlankForm $blankForm) use ($loadedTest) {
            $variantNumber = $this->testVariantService->normalizeVariantNumber($loadedTest, $blankForm->variant_number ?? 1);

            return [
                (int) $blankForm->id => $this->testPrintLayoutService->paginateQuestions($loadedTest, $variantNumber),
            ];
        })->all();

        return view('tests.print', [
            'test' => $loadedTest,
            'blankForms' => $blankForms,
            'documentTitle' => $documentTitle,
            'printMode' => $printMode,
            'answerSheetPagesByBlankForm' => $answerSheetPagesByBlankForm,
            'questionPagesByBlankForm' => $questionPagesByBlankForm,
        ]);
    }

    private function buildPrintDocumentTitle(Test $test, $blankForms, string $printMode): string
    {
        $firstBlankForm = $blankForms->first();
        $studentLabel = trim(implode(' ', array_filter([
            $firstBlankForm?->last_name,
            $firstBlankForm?->first_name,
        ])));
        $modeLabel = match ($printMode) {
            'blank' => 'Бланк',
            'questions' => 'Задания',
            default => 'Комплект',
        };

        if ($blankForms->count() === 1 && $studentLabel !== '') {
            return $this->sanitizeDocumentTitle("{$modeLabel} {$studentLabel} {$test->title}");
        }

        $groupLabel = trim((string) ($firstBlankForm?->group_name ?? ''));
        $baseTitle = "{$modeLabel} {$test->title}";

        if ($groupLabel !== '') {
            $baseTitle .= " {$groupLabel}";
        }

        return $this->sanitizeDocumentTitle($baseTitle);
    }

    private function normalizePrintMode(string $value): string
    {
        return match (Str::lower(trim($value))) {
            'blank', 'questions' => Str::lower(trim($value)),
            default => 'all',
        };
    }

    private function sanitizeDocumentTitle(string $value): string
    {
        return Str::of($value)
            ->replaceMatches('/[\\\\\\/:*?"<>|]+/u', ' ')
            ->squish()
            ->limit(150, '')
            ->value();
    }
}
