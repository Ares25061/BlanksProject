<?php

namespace App\Http\Controllers;

use App\Models\ElectronicTestAttempt;
use App\Models\StudentGroup;
use App\Models\Test;
use App\Services\ElectronicTestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ElectronicTestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ElectronicTestService $electronicTestService,
    ) {
    }

    public function dashboard(Test $test)
    {
        $this->authorize('view', $test);

        return response()->json([
            'status' => 'success',
            'data' => $this->electronicTestService->buildTeacherDashboard($test),
        ]);
    }

    public function showTeacherAttempt(ElectronicTestAttempt $attempt)
    {
        $this->authorize('view', $attempt->test);

        return response()->json([
            'status' => 'success',
            'data' => $this->electronicTestService->buildTeacherAttempt($attempt),
        ]);
    }

    public function launch(Request $request, Test $test)
    {
        $this->authorize('update', $test);

        $validated = $request->validate([
            'student_group_id' => 'required|integer|exists:student_groups,id',
            'variant_assignment_mode' => 'nullable|in:same,balanced,custom',
            'variant_number' => 'nullable|integer|min:1|max:10',
            'variant_numbers' => 'nullable|array',
        ]);

        $group = StudentGroup::query()->findOrFail((int) $validated['student_group_id']);
        $this->authorize('view', $group);

        $session = $this->electronicTestService->launchSession($test, $group, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Электронный запуск теста создан.',
            'data' => $this->electronicTestService->buildTeacherDashboard($test)['current_session'],
        ]);
    }

    public function resolveCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $session = $this->electronicTestService->resolveSessionByCode($validated['code']);

        return response()->json([
            'status' => 'success',
            'data' => $this->electronicTestService->buildPublicSessionPayload($session),
        ]);
    }

    public function session(string $token)
    {
        $session = $this->electronicTestService->getSessionByToken($token);

        return response()->json([
            'status' => 'success',
            'data' => $this->electronicTestService->buildPublicSessionPayload($session),
        ]);
    }

    public function member(string $token)
    {
        $member = $this->electronicTestService->getMemberByToken($token);

        return response()->json([
            'status' => 'success',
            'data' => $this->electronicTestService->buildPublicSessionPayload($member->session, $member),
        ]);
    }

    public function startFromSession(Request $request, string $token)
    {
        $validated = $request->validate([
            'group_student_id' => 'nullable|integer',
            'manual_full_name' => 'nullable|string|max:255',
        ]);

        $session = $this->electronicTestService->getSessionByToken($token);

        return response()->json([
            'status' => 'success',
            'message' => 'Тестирование начато.',
            'data' => $this->electronicTestService->startAttemptForSession($session, $validated),
        ]);
    }

    public function startFromMember(Request $request, string $token)
    {
        $member = $this->electronicTestService->getMemberByToken($token);

        return response()->json([
            'status' => 'success',
            'message' => 'Тестирование начато.',
            'data' => $this->electronicTestService->startAttemptForSession($member->session, [], $member),
        ]);
    }

    public function showAttempt(string $token)
    {
        $attempt = $this->electronicTestService->getAttemptByToken($token);

        return response()->json([
            'status' => 'success',
            'data' => $this->electronicTestService->buildAttemptPayload($attempt),
        ]);
    }

    public function submitAttempt(Request $request, string $token)
    {
        $validated = $request->validate([
            'answers' => 'required|array',
        ]);

        $attempt = $this->electronicTestService->getAttemptByToken($token);
        $attempt = $this->electronicTestService->submitAttempt($attempt, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Работа отправлена преподавателю.',
            'data' => [
                'attempt_id' => $attempt->id,
                'student_full_name' => $attempt->student_full_name,
                'submitted_at' => optional($attempt->submitted_at)->toIso8601String(),
            ],
        ]);
    }

    public function logAttempt(Request $request, string $token)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:50',
            'payload' => 'nullable|array',
            'occurred_at' => 'nullable|date',
        ]);

        $attempt = $this->electronicTestService->getAttemptByToken($token);
        $log = $this->electronicTestService->appendAttemptLog($attempt, $validated);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $log->id,
            ],
        ]);
    }

    public function assignGrade(Request $request, ElectronicTestAttempt $attempt)
    {
        $this->authorize('update', $attempt->test);

        $validated = $request->validate([
            'grade_value' => 'required|string|max:50',
            'grade_date' => 'required|date',
        ]);

        $attempt = $this->electronicTestService->assignGrade($attempt, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Оценка по электронной работе сохранена.',
            'data' => $attempt,
        ]);
    }

    public function attachStudent(Request $request, ElectronicTestAttempt $attempt)
    {
        $this->authorize('update', $attempt->test);

        $validated = $request->validate([
            'student_full_name' => 'nullable|string|max:255',
            'grade_value' => 'nullable|string|max:50',
            'grade_date' => 'nullable|date',
        ]);

        $attempt = $this->electronicTestService->attachStudentAndOptionallyGrade($attempt, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Ученик привязан к группе.',
            'data' => $attempt,
        ]);
    }
}
