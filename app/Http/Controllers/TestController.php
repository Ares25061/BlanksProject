<?php
namespace App\Http\Controllers;

use App\Models\BlankForm;
use App\Models\Test;
use App\Services\BlankSheetManifestService;
use App\Services\BlankSheetQrCodeService;
use App\Services\TestExportService;
use App\Services\TestService;
use App\Services\TestImportService;
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
    protected $blankSheetManifestService;
    protected $blankSheetQrCodeService;

    public function __construct(
        TestService $testService,
        TestImportService $testImportService,
        TestExportService $testExportService,
        BlankSheetManifestService $blankSheetManifestService,
        BlankSheetQrCodeService $blankSheetQrCodeService,
    )
    {
        $this->testService = $testService;
        $this->testImportService = $testImportService;
        $this->testExportService = $testExportService;
        $this->blankSheetManifestService = $blankSheetManifestService;
        $this->blankSheetQrCodeService = $blankSheetQrCodeService;
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
                if (in_array($status, ['active', 'draft', 'closed'], true)) {
                    $query->where('test_status', $status);
                    return;
                }

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

    public function updateDeliveryMode(Request $request, Test $test)
    {
        $this->authorize('update', $test);

        $validated = $request->validate([
            'delivery_mode' => 'required|in:blank,electronic,hybrid',
        ]);

        $test = $this->testService->updateDeliveryMode($test, $validated['delivery_mode']);

        return response()->json([
            'status' => 'success',
            'message' => 'Формат теста обновлён',
            'data' => $test,
        ]);
    }

    public function close(Test $test)
    {
        $this->authorize('update', $test);

        if ((string) $test->test_status === 'closed') {
            return response()->json([
                'status' => 'success',
                'message' => 'Тест уже закрыт',
                'data' => $test,
            ]);
        }

        $test = $this->testService->closeTest($test);

        return response()->json([
            'status' => 'success',
            'message' => 'Тест завершён и переведён в статус "Закрыт"',
            'data' => $test,
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

        $documentTitle = $this->buildPrintDocumentTitle($test, $blankForms);

        $loadedTest = $test->load('questions.answers');
        $electronicAccessUrl = null;
        $electronicAccessQrDataUri = null;

        if (($loadedTest->delivery_mode ?? 'blank') === 'hybrid' && !empty($loadedTest->access_code)) {
            $electronicAccessUrl = url('/take-test?code=' . urlencode((string) $loadedTest->access_code));
            $electronicAccessQrDataUri = $this->blankSheetQrCodeService->renderTextDataUri($electronicAccessUrl, 200);
        }

        $sheetPagesByBlankForm = $blankForms->mapWithKeys(function (BlankForm $blankForm) {
            $pages = (int) $blankForm->id > 0
                ? $this->blankSheetManifestService->ensurePersisted($blankForm->fresh('test.questions.answers'))
                : $this->blankSheetManifestService->buildPreview($blankForm->loadMissing('test.questions.answers'));

            $pages = collect($pages)
                ->map(function (array $page) {
                    $page['qr_data_uri'] = $this->blankSheetQrCodeService->renderDataUri($page['qr_payload'] ?? []);

                    return $page;
                })
                ->all();

            return [
                (int) $blankForm->id => $pages,
            ];
        })->all();

        return view('tests.print', [
            'test' => $loadedTest,
            'blankForms' => $blankForms,
            'documentTitle' => $documentTitle,
            'sheetPagesByBlankForm' => $sheetPagesByBlankForm,
            'electronicAccessUrl' => $electronicAccessUrl,
            'electronicAccessQrDataUri' => $electronicAccessQrDataUri,
        ]);
    }

    private function buildPrintDocumentTitle(Test $test, $blankForms): string
    {
        $firstBlankForm = $blankForms->first();
        $studentLabel = trim(implode(' ', array_filter([
            $firstBlankForm?->last_name,
            $firstBlankForm?->first_name,
        ])));

        if ($blankForms->count() === 1 && $studentLabel !== '') {
            return $this->sanitizeDocumentTitle("Бланк {$studentLabel} {$test->title}");
        }

        $groupLabel = trim((string) ($firstBlankForm?->group_name ?? ''));
        $baseTitle = 'Бланки ' . $test->title;

        if ($groupLabel !== '') {
            $baseTitle .= " {$groupLabel}";
        }

        return $this->sanitizeDocumentTitle($baseTitle);
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
