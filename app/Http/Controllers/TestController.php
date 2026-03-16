<?php
namespace App\Http\Controllers;

use App\Models\Test;
use App\Services\TestService;
use App\Http\Requests\TestRequest;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TestController extends Controller
{
    use AuthorizesRequests;

    protected $testService;

    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Test::class);

        $tests = Test::with(['creator', 'questions.answers'])
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
            'answers' => 'required|array|min:1',
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
}
