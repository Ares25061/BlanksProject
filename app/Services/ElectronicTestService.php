<?php

namespace App\Services;

use App\Models\ElectronicTestAnswer;
use App\Models\ElectronicTestAttempt;
use App\Models\ElectronicTestLog;
use App\Models\ElectronicTestSession;
use App\Models\ElectronicTestSessionMember;
use App\Models\GroupStudent;
use App\Models\StudentGroup;
use App\Models\Test;
use App\Support\StudentName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ElectronicTestService
{
    public function __construct(
        private TestVariantService $testVariantService,
        private StudentGradeService $studentGradeService,
    ) {
    }

    public function ensureTestAccessCode(Test $test): Test
    {
        if (trim((string) $test->access_code) !== '') {
            return $test;
        }

        $test->forceFill([
            'access_code' => $this->generateUniqueTestAccessCode(),
        ])->save();

        return $test->refresh();
    }

    public function launchSession(Test $test, StudentGroup $group, array $data): ElectronicTestSession
    {
        $test = $this->ensureTestAccessCode($test->fresh());

        if ((string) $test->test_status === 'closed') {
            throw ValidationException::withMessages([
                'test' => 'Тест закрыт. Электронное прохождение для него недоступно.',
            ]);
        }

        if ($this->normalizeDeliveryMode($test->delivery_mode) === 'blank') {
            throw ValidationException::withMessages([
                'delivery_mode' => 'Для этого теста сейчас включён только режим бланков. Переключите формат теста на электронный или совмещённый.',
            ]);
        }

        $students = $group->students()->get();
        if ($students->isEmpty()) {
            throw ValidationException::withMessages([
                'student_group_id' => 'В выбранной группе нет учеников для электронного тестирования.',
            ]);
        }

        return DB::transaction(function () use ($test, $group, $students, $data) {
            ElectronicTestSession::query()
                ->where('test_id', $test->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'ended_at' => now(),
                ]);

            $session = ElectronicTestSession::create([
                'test_id' => $test->id,
                'student_group_id' => $group->id,
                'created_by' => Auth::id(),
                'access_token' => $this->generateUniqueToken(ElectronicTestSession::class, 'access_token'),
                'is_active' => true,
                'settings' => [
                    'variant_assignment_mode' => $this->normalizeVariantAssignmentMode($data['variant_assignment_mode'] ?? null),
                    'default_variant_number' => $this->resolveDefaultVariantNumber($test, $data),
                    'anti_cheat' => [
                        'fullscreen_required' => true,
                        'log_focus_events' => true,
                        'log_pointer_leave' => true,
                    ],
                ],
                'started_at' => now(),
            ]);

            $variantAssignments = $this->resolveGroupVariantAssignments($test, $students, $data);

            foreach ($students as $student) {
                ElectronicTestSessionMember::create([
                    'electronic_test_session_id' => $session->id,
                    'group_student_id' => $student->id,
                    'variant_number' => $variantAssignments[(int) $student->id] ?? 1,
                    'access_token' => $this->generateUniqueToken(ElectronicTestSessionMember::class, 'access_token'),
                ]);
            }

            return $session->load([
                'test',
                'studentGroup.students',
                'members.groupStudent',
            ]);
        });
    }

    public function buildTeacherDashboard(Test $test): array
    {
        $test = $this->ensureTestAccessCode($test->fresh());
        $currentSession = $test->electronicSessions()
            ->with([
                'studentGroup',
                'members.groupStudent',
                'attempts.answers.question.answers',
                'attempts.logs',
                'attempts.groupStudent',
                'attempts.gradeAssigner',
            ])
            ->where('is_active', true)
            ->first();

        return [
            'delivery_mode' => $this->normalizeDeliveryMode($test->delivery_mode),
            'delivery_mode_label' => $test->delivery_mode_label,
            'access_code' => $test->access_code,
            'code_link' => $this->buildCodeLink($test),
            'current_session' => $currentSession ? $this->serializeSessionForTeacher($currentSession) : null,
        ];
    }

    public function buildTeacherAttempt(ElectronicTestAttempt $attempt): array
    {
        $attempt->loadMissing([
            'test',
            'session.studentGroup',
            'groupStudent',
            'gradeAssigner',
            'answers.question.answers',
            'logs',
        ]);

        return [
            'attempt' => $this->serializeAttemptForTeacher($attempt),
            'test' => $attempt->test ? [
                'id' => $attempt->test->id,
                'title' => $attempt->test->title,
                'subject_name' => $attempt->test->subject_display_name,
                'delivery_mode' => $this->normalizeDeliveryMode($attempt->test->delivery_mode),
                'delivery_mode_label' => $attempt->test->delivery_mode_label,
            ] : null,
            'session' => $attempt->session ? [
                'id' => $attempt->session->id,
                'group' => $attempt->session->studentGroup ? [
                    'id' => $attempt->session->studentGroup->id,
                    'name' => $attempt->session->studentGroup->name,
                ] : null,
            ] : null,
        ];
    }

    public function resolveSessionByCode(string $code): ElectronicTestSession
    {
        $normalizedCode = Str::upper(trim($code));
        if ($normalizedCode === '') {
            throw ValidationException::withMessages([
                'code' => 'Введите код теста.',
            ]);
        }

        $test = Test::query()
            ->where('access_code', $normalizedCode)
            ->first();

        if (!$test) {
            throw ValidationException::withMessages([
                'code' => 'Тест с таким кодом не найден.',
            ]);
        }

        if ($this->normalizeDeliveryMode($test->delivery_mode) === 'blank') {
            throw ValidationException::withMessages([
                'code' => 'Для этого теста не включено электронное прохождение.',
            ]);
        }

        if ((string) $test->test_status === 'closed') {
            throw ValidationException::withMessages([
                'code' => 'Этот тест уже закрыт. Прохождение недоступно.',
            ]);
        }

        $session = $test->electronicSessions()
            ->with(['test', 'studentGroup.students', 'members.groupStudent'])
            ->where('is_active', true)
            ->first();

        if (!$session) {
            throw ValidationException::withMessages([
                'code' => 'Для этого теста сейчас нет активного электронного запуска.',
            ]);
        }

        return $session;
    }

    public function getSessionByToken(string $token): ElectronicTestSession
    {
        $session = ElectronicTestSession::query()
            ->with(['test', 'studentGroup.students', 'members.groupStudent'])
            ->where('access_token', trim($token))
            ->first();

        if (!$session) {
            throw ValidationException::withMessages([
                'session' => 'Ссылка на тест недействительна или устарела.',
            ]);
        }

        if ((string) $session->test?->test_status === 'closed') {
            throw ValidationException::withMessages([
                'session' => 'Этот тест уже закрыт. Прохождение недоступно.',
            ]);
        }

        return $session;
    }

    public function getMemberByToken(string $token): ElectronicTestSessionMember
    {
        $member = ElectronicTestSessionMember::query()
            ->with(['session.test', 'session.studentGroup.students', 'groupStudent'])
            ->where('access_token', trim($token))
            ->first();

        if (!$member) {
            throw ValidationException::withMessages([
                'session' => 'Персональная ссылка на тест недействительна или устарела.',
            ]);
        }

        if ((string) $member->session?->test?->test_status === 'closed') {
            throw ValidationException::withMessages([
                'session' => 'Этот тест уже закрыт. Прохождение недоступно.',
            ]);
        }

        return $member;
    }

    public function buildPublicSessionPayload(ElectronicTestSession $session, ?ElectronicTestSessionMember $member = null): array
    {
        $session->loadMissing(['test', 'studentGroup.students', 'members.groupStudent']);
        $test = $session->test;

        return [
            'session' => [
                'token' => $session->access_token,
                'title' => $test->title,
                'subject_name' => $test->subject_display_name,
                'description' => $test->description,
                'time_limit' => $test->time_limit,
                'delivery_mode' => $this->normalizeDeliveryMode($test->delivery_mode),
                'delivery_mode_label' => $test->delivery_mode_label,
                'access_code' => $test->access_code,
                'group' => $session->studentGroup ? [
                    'id' => $session->studentGroup->id,
                    'name' => $session->studentGroup->name,
                ] : null,
                'requires_fullscreen' => true,
                'allow_manual_student' => true,
                'students' => $member
                    ? []
                    : $session->studentGroup?->students
                        ?->map(fn (GroupStudent $student) => [
                            'id' => $student->id,
                            'full_name' => $student->full_name,
                        ])
                        ->values()
                        ->all() ?? [],
                'prefilled_student' => $member ? [
                    'id' => $member->group_student_id,
                    'full_name' => $member->groupStudent?->full_name,
                ] : null,
            ],
        ];
    }

    public function startAttemptForSession(ElectronicTestSession $session, array $data, ?ElectronicTestSessionMember $member = null): array
    {
        $session->loadMissing(['test.questions.answers', 'studentGroup.students', 'members.groupStudent']);

        if (!$session->is_active) {
            throw ValidationException::withMessages([
                'session' => 'Этот запуск теста уже завершён.',
            ]);
        }

        if ((string) $session->test?->test_status === 'closed') {
            throw ValidationException::withMessages([
                'session' => 'Тест закрыт. Начать прохождение уже нельзя.',
            ]);
        }

        [$resolvedMember, $groupStudentId, $studentGroupId, $fullName, $variantNumber, $isManualStudent, $accessType] = $this->resolveAttemptStudentContext(
            $session,
            $data,
            $member
        );

        $existingAttempt = $this->findReusableAttempt($session, $groupStudentId, $fullName);
        if ($existingAttempt) {
            return $this->buildAttemptPayload($existingAttempt->fresh([
                'test.questions.answers',
                'answers',
                'logs',
            ]), true);
        }

        $submittedAttempt = $this->findSubmittedAttempt($session, $groupStudentId, $fullName);
        if ($submittedAttempt) {
            throw ValidationException::withMessages([
                'student' => 'Для этого ученика работа уже отправлена. Повторное прохождение сейчас недоступно.',
            ]);
        }

        $attempt = ElectronicTestAttempt::create([
            'electronic_test_session_id' => $session->id,
            'electronic_test_session_member_id' => $resolvedMember?->id,
            'test_id' => $session->test_id,
            'student_group_id' => $studentGroupId,
            'group_student_id' => $groupStudentId,
            'variant_number' => $variantNumber,
            'access_token' => $this->generateUniqueToken(ElectronicTestAttempt::class, 'access_token'),
            'access_type' => $accessType,
            'student_full_name' => $fullName,
            'is_manual_student' => $isManualStudent,
            'status' => 'in_progress',
            'metadata' => [
                'session_started_via' => $accessType,
            ],
            'started_at' => now(),
        ]);

        $this->appendAttemptLog($attempt, [
            'event_type' => 'start',
            'payload' => [
                'started_via' => $accessType,
            ],
        ]);

        return $this->buildAttemptPayload($attempt->fresh('test.questions.answers', 'answers', 'logs'), false);
    }

    public function getAttemptByToken(string $token): ElectronicTestAttempt
    {
        $attempt = ElectronicTestAttempt::query()
            ->with(['test.questions.answers', 'answers', 'logs', 'groupStudent', 'session.studentGroup'])
            ->where('access_token', trim($token))
            ->first();

        if (!$attempt) {
            throw ValidationException::withMessages([
                'attempt' => 'Попытка тестирования не найдена или уже недоступна.',
            ]);
        }

        return $attempt;
    }

    public function buildAttemptPayload(ElectronicTestAttempt $attempt, bool $resumed = false): array
    {
        $attempt->loadMissing(['test.questions.answers', 'answers', 'session.studentGroup', 'groupStudent']);
        $test = $attempt->test;
        $questions = $this->testVariantService
            ->questionsForVariant($test, $attempt->variant_number)
            ->values();

        $answerMap = $attempt->answers
            ->mapWithKeys(function (ElectronicTestAnswer $answer) {
                return [
                    (int) $answer->question_id => $answer->selected_answers
                        ?: ($answer->answer_id ? [$answer->answer_id] : []),
                ];
            })
            ->all();

        return [
            'attempt' => [
                'token' => $attempt->access_token,
                'student_full_name' => $attempt->student_full_name,
                'variant_number' => $attempt->variant_number,
                'status' => $attempt->status,
                'started_at' => optional($attempt->started_at)->toIso8601String(),
                'time_limit' => $test->time_limit,
                'resumed' => $resumed,
                'group_name' => $attempt->session->studentGroup?->name,
                'is_manual_student' => $attempt->is_manual_student,
                'questions' => $questions->map(function ($question) use ($answerMap) {
                    return [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'type' => $question->type,
                        'points' => (int) $question->points,
                        'answers' => $question->answers->map(fn ($answer) => [
                            'id' => $answer->id,
                            'answer_text' => $answer->answer_text,
                        ])->values()->all(),
                        'selected_answers' => $answerMap[(int) $question->id] ?? [],
                    ];
                })->all(),
            ],
            'session' => [
                'title' => $test->title,
                'subject_name' => $test->subject_display_name,
                'description' => $test->description,
            ],
        ];
    }

    public function submitAttempt(ElectronicTestAttempt $attempt, array $data): ElectronicTestAttempt
    {
        $attempt->loadMissing(['test.questions.answers', 'session.studentGroup']);

        if (in_array($attempt->status, ['submitted', 'reviewed'], true)) {
            throw ValidationException::withMessages([
                'attempt' => 'Эта работа уже отправлена преподавателю.',
            ]);
        }

        $questions = $this->testVariantService
            ->questionsForVariant($attempt->test, $attempt->variant_number)
            ->keyBy('id');

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'attempt' => 'Для выбранного варианта не найдено ни одного вопроса.',
            ]);
        }

        $submittedAnswers = collect($data['answers'] ?? [])
            ->mapWithKeys(fn ($value, $key) => [(int) $key => $value])
            ->all();

        return DB::transaction(function () use ($attempt, $questions, $submittedAnswers) {
            $attempt->answers()->delete();

            $totalScore = 0;

            foreach ($questions as $questionId => $question) {
                $selectedAnswerIds = collect(is_array($submittedAnswers[$questionId] ?? null) ? $submittedAnswers[$questionId] : [$submittedAnswers[$questionId] ?? null])
                    ->map(fn ($value) => (int) $value)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                [$answerId, $selectedAnswers, $isCorrect, $pointsEarned] = $this->evaluateQuestionSelection($question, $selectedAnswerIds);

                ElectronicTestAnswer::create([
                    'electronic_test_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'answer_id' => $answerId,
                    'selected_answers' => $selectedAnswers,
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                ]);

                $totalScore += $pointsEarned;
            }

            $attempt->update([
                'status' => 'submitted',
                'total_score' => $totalScore,
                'grade_label' => $this->calculateGradeLabel($attempt->test, $totalScore, (int) $questions->sum('points')),
                'submitted_at' => now(),
            ]);

            $this->appendAttemptLog($attempt->fresh(), [
                'event_type' => 'submit',
                'payload' => [
                    'total_score' => $totalScore,
                ],
            ]);

            return $attempt->fresh([
                'test.questions.answers',
                'answers.question.answers',
                'logs',
                'groupStudent',
                'session.studentGroup',
            ]);
        });
    }

    public function appendAttemptLog(ElectronicTestAttempt $attempt, array $data): ElectronicTestLog
    {
        $eventType = trim((string) ($data['event_type'] ?? ''));
        if ($eventType === '') {
            throw ValidationException::withMessages([
                'event_type' => 'Не указан тип события журнала.',
            ]);
        }

        return ElectronicTestLog::create([
            'electronic_test_attempt_id' => $attempt->id,
            'event_type' => Str::limit($eventType, 50, ''),
            'payload' => $data['payload'] ?? null,
            'occurred_at' => !empty($data['occurred_at']) ? now()->parse($data['occurred_at']) : now(),
        ]);
    }

    public function assignGrade(ElectronicTestAttempt $attempt, array $data): ElectronicTestAttempt
    {
        if (!$attempt->group_student_id || !$attempt->student_group_id) {
            throw ValidationException::withMessages([
                'attempt' => 'Сначала привяжите эту работу к ученику группы.',
            ]);
        }

        $attempt->update([
            'assigned_grade_value' => trim((string) $data['grade_value']),
            'assigned_grade_date' => $data['grade_date'],
            'assigned_grade_by' => Auth::id(),
            'reviewed_at' => now(),
            'status' => 'reviewed',
        ]);

        $this->syncGradebookFromAttempt($attempt->fresh(['test', 'studentGroup']));

        return $attempt->fresh([
            'answers.question.answers',
            'logs',
            'groupStudent',
            'gradeAssigner',
            'session.studentGroup',
            'test.questions.answers',
        ]);
    }

    public function attachStudentAndOptionallyGrade(ElectronicTestAttempt $attempt, array $data): ElectronicTestAttempt
    {
        $attempt->loadMissing(['session.studentGroup', 'test']);
        $group = $attempt->session->studentGroup;

        if (!$group) {
            throw ValidationException::withMessages([
                'attempt' => 'У текущего запуска теста не выбрана учебная группа.',
            ]);
        }

        $fullName = trim((string) ($data['student_full_name'] ?? $attempt->student_full_name));
        if ($fullName === '') {
            throw ValidationException::withMessages([
                'student_full_name' => 'Укажите ФИО ученика.',
            ]);
        }

        $existingStudent = $group->students()
            ->whereRaw('LOWER(full_name) = ?', [mb_strtolower($fullName)])
            ->first();

        if (!$existingStudent) {
            $existingStudent = $group->students()->create([
                'full_name' => $fullName,
                'sort_order' => ($group->students()->max('sort_order') ?? -1) + 1,
            ]);
        }

        $attempt->update([
            'student_group_id' => $group->id,
            'group_student_id' => $existingStudent->id,
            'student_full_name' => $existingStudent->full_name,
        ]);

        if (!empty($data['grade_value']) && !empty($data['grade_date'])) {
            return $this->assignGrade($attempt->fresh(), [
                'grade_value' => $data['grade_value'],
                'grade_date' => $data['grade_date'],
            ]);
        }

        return $attempt->fresh([
            'groupStudent',
            'session.studentGroup',
            'answers.question.answers',
            'logs',
            'test.questions.answers',
        ]);
    }

    private function serializeSessionForTeacher(ElectronicTestSession $session): array
    {
        $attempts = $session->attempts
            ->map(fn (ElectronicTestAttempt $attempt) => $this->serializeAttemptSummaryForTeacher($attempt))
            ->values()
            ->all();

        return [
            'id' => $session->id,
            'token' => $session->access_token,
            'started_at' => optional($session->started_at)->toIso8601String(),
            'group' => $session->studentGroup ? [
                'id' => $session->studentGroup->id,
                'name' => $session->studentGroup->name,
            ] : null,
            'general_link' => $this->buildSessionLink($session),
            'members' => $session->members->map(function (ElectronicTestSessionMember $member) {
                return [
                    'id' => $member->id,
                    'group_student_id' => $member->group_student_id,
                    'full_name' => $member->groupStudent?->full_name,
                    'variant_number' => $member->variant_number,
                    'personal_link' => $this->buildMemberLink($member),
                ];
            })->values()->all(),
            'attempts' => $attempts,
            'unreviewed_count' => collect($attempts)->whereIn('status', ['submitted'])->count(),
        ];
    }

    private function serializeAttemptForTeacher(ElectronicTestAttempt $attempt): array
    {
        $summary = $this->serializeAttemptSummaryForTeacher($attempt);

        return array_merge($summary, [
            'logs' => $attempt->logs->map(fn (ElectronicTestLog $log) => [
                'id' => $log->id,
                'event_type' => $log->event_type,
                'event_label' => $this->eventTypeLabel((string) $log->event_type),
                'payload' => $log->payload,
                'payload_summary' => $this->describeLogPayload((string) $log->event_type, $log->payload),
                'occurred_at' => optional($log->occurred_at)->toIso8601String(),
            ])->values()->all(),
            'answers' => $attempt->answers->map(function (ElectronicTestAnswer $answer) {
                $question = $answer->question;
                $selectedAnswerIds = $answer->selected_answers ?: ($answer->answer_id ? [$answer->answer_id] : []);
                $selectedAnswerTexts = collect($question?->answers ?? [])
                    ->whereIn('id', $selectedAnswerIds)
                    ->pluck('answer_text')
                    ->values()
                    ->all();

                return [
                    'question_id' => $answer->question_id,
                    'question_text' => $question?->question_text,
                    'points' => (int) ($question?->points ?? 0),
                    'selected_answers' => $selectedAnswerIds,
                    'selected_answer_texts' => $selectedAnswerTexts,
                    'is_correct' => (bool) $answer->is_correct,
                    'points_earned' => (int) ($answer->points_earned ?? 0),
                ];
            })->values()->all(),
        ]);
    }

    private function serializeAttemptSummaryForTeacher(ElectronicTestAttempt $attempt): array
    {
        $logSummary = collect($attempt->logs)
            ->groupBy('event_type')
            ->map(fn (Collection $items) => $items->count())
            ->all();

        return [
            'id' => $attempt->id,
            'status' => $attempt->status,
            'status_label' => $this->attemptStatusLabel((string) $attempt->status),
            'student_full_name' => $attempt->student_full_name,
            'group_student_id' => $attempt->group_student_id,
            'variant_number' => $attempt->variant_number,
            'is_manual_student' => $attempt->is_manual_student,
            'total_score' => $attempt->total_score,
            'grade_label' => $attempt->grade_label,
            'assigned_grade_value' => $attempt->assigned_grade_value,
            'assigned_grade_date' => optional($attempt->assigned_grade_date)->format('Y-m-d'),
            'submitted_at' => optional($attempt->submitted_at)->toIso8601String(),
            'reviewed_at' => optional($attempt->reviewed_at)->toIso8601String(),
            'log_summary' => $logSummary,
            'log_summary_items' => $this->buildLogSummaryItems(collect($attempt->logs)),
            'review_url' => url('/electronic-attempts/' . $attempt->id),
        ];
    }

    private function buildLogSummaryItems(Collection $logs): array
    {
        $counts = $logs
            ->groupBy('event_type')
            ->map(fn (Collection $items) => $items->count());

        $orderedEventTypes = [
            'visibility_hidden',
            'window_blur',
            'fullscreen_exit',
            'pointer_leave_page',
            'window_resize',
            'fullscreen_denied',
            'submit',
            'start',
        ];

        return collect($orderedEventTypes)
            ->map(function (string $eventType) use ($counts) {
                $count = (int) ($counts[$eventType] ?? 0);

                return [
                    'event_type' => $eventType,
                    'label' => $this->eventTypeLabel($eventType),
                    'count' => $count,
                ];
            })
            ->filter(fn (array $item) => $item['count'] > 0)
            ->values()
            ->all();
    }

    private function attemptStatusLabel(string $status): string
    {
        return match ($status) {
            'reviewed' => 'Проверена',
            'submitted' => 'Отправлена',
            'in_progress' => 'В процессе',
            default => 'Неизвестно',
        };
    }

    private function eventTypeLabel(string $eventType): string
    {
        return match ($eventType) {
            'start' => 'Начало теста',
            'submit' => 'Отправка работы',
            'window_blur' => 'Потеря фокуса окна',
            'window_focus' => 'Возврат в окно',
            'visibility_hidden' => 'Скрытие вкладки',
            'visibility_visible' => 'Возврат на вкладку',
            'fullscreen_enter' => 'Вход в полный экран',
            'fullscreen_exit' => 'Выход из полного экрана',
            'window_resize' => 'Изменение размера окна',
            'pointer_leave_page' => 'Курсор покинул страницу',
            'fullscreen_denied' => 'Полный экран отклонён',
            default => $eventType,
        };
    }

    private function describeLogPayload(string $eventType, mixed $payload): ?string
    {
        if (!is_array($payload) || $payload === []) {
            return null;
        }

        return match ($eventType) {
            'start' => !empty($payload['started_via'])
                ? 'Способ запуска: ' . match ((string) $payload['started_via']) {
                    'student_link' => 'персональная ссылка',
                    'student_list' => 'выбор из списка',
                    'manual_name' => 'ручной ввод ФИО',
                    default => 'общая ссылка',
                }
                : null,
            'submit' => isset($payload['total_score'])
                ? 'Итоговый балл: ' . (int) $payload['total_score']
                : null,
            'window_resize' => !empty($payload['width']) && !empty($payload['height'])
                ? 'Размер окна: ' . (int) $payload['width'] . ' × ' . (int) $payload['height']
                : null,
            'fullscreen_denied' => !empty($payload['message'])
                ? 'Причина: ' . trim((string) $payload['message'])
                : null,
            default => null,
        };
    }

    private function resolveAttemptStudentContext(ElectronicTestSession $session, array $data, ?ElectronicTestSessionMember $member): array
    {
        if ($member) {
            $fullName = trim((string) $member->groupStudent?->full_name);

            return [
                $member,
                $member->group_student_id,
                $session->student_group_id,
                $fullName,
                $member->variant_number,
                false,
                'student_link',
            ];
        }

        $groupStudentId = (int) ($data['group_student_id'] ?? 0);
        if ($groupStudentId > 0) {
            $resolvedMember = $session->members
                ->first(fn (ElectronicTestSessionMember $item) => (int) $item->group_student_id === $groupStudentId);

            if (!$resolvedMember) {
                throw ValidationException::withMessages([
                    'group_student_id' => 'Выбранный ученик не относится к текущему запуску теста.',
                ]);
            }

            return [
                $resolvedMember,
                $resolvedMember->group_student_id,
                $session->student_group_id,
                (string) $resolvedMember->groupStudent?->full_name,
                $resolvedMember->variant_number,
                false,
                'student_list',
            ];
        }

        $manualName = trim((string) ($data['manual_full_name'] ?? ''));
        if ($manualName === '') {
            throw ValidationException::withMessages([
                'manual_full_name' => 'Выберите себя из списка или введите ФИО вручную.',
            ]);
        }

        $parsedName = StudentName::parse($manualName);

        return [
            null,
            null,
            $session->student_group_id,
            StudentName::format($parsedName['last_name'], $parsedName['first_name'], $parsedName['patronymic']),
            (int) data_get($session->settings, 'default_variant_number', 1),
            true,
            'manual_name',
        ];
    }

    private function findReusableAttempt(ElectronicTestSession $session, ?int $groupStudentId, string $fullName): ?ElectronicTestAttempt
    {
        return ElectronicTestAttempt::query()
            ->where('electronic_test_session_id', $session->id)
            ->where('status', 'in_progress')
            ->when($groupStudentId, fn ($query) => $query->where('group_student_id', $groupStudentId), function ($query) use ($fullName) {
                $query->whereRaw('LOWER(student_full_name) = ?', [mb_strtolower($fullName)]);
            })
            ->latest('id')
            ->first();
    }

    private function findSubmittedAttempt(ElectronicTestSession $session, ?int $groupStudentId, string $fullName): ?ElectronicTestAttempt
    {
        return ElectronicTestAttempt::query()
            ->where('electronic_test_session_id', $session->id)
            ->whereIn('status', ['submitted', 'reviewed'])
            ->when($groupStudentId, fn ($query) => $query->where('group_student_id', $groupStudentId), function ($query) use ($fullName) {
                $query->whereRaw('LOWER(student_full_name) = ?', [mb_strtolower($fullName)]);
            })
            ->latest('id')
            ->first();
    }

    private function evaluateQuestionSelection($question, array $selectedAnswerIds): array
    {
        if ($question->type === 'single') {
            $selectedAnswerIds = array_values(array_slice($selectedAnswerIds, 0, 1));
            $answerId = $selectedAnswerIds[0] ?? null;
            $correctAnswer = $question->answers->first(fn ($answer) => (bool) $answer->is_correct);
            $isCorrect = $correctAnswer && $answerId === (int) $correctAnswer->id;

            return [
                $answerId,
                $selectedAnswerIds,
                (bool) $isCorrect,
                $isCorrect ? (int) $question->points : 0,
            ];
        }

        $correctIds = $question->answers
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        sort($correctIds);
        sort($selectedAnswerIds);
        $isCorrect = $correctIds === $selectedAnswerIds;

        return [
            null,
            $selectedAnswerIds,
            $isCorrect,
            $isCorrect ? (int) $question->points : 0,
        ];
    }

    private function calculateGradeLabel(Test $test, int $score, int $maxScore): string
    {
        $criteria = collect($test->grade_criteria ?? [])
            ->sortByDesc('min_points')
            ->values();

        if ($criteria->isNotEmpty()) {
            $criterion = $criteria->first(fn ($item) => $score >= (int) ($item['min_points'] ?? 0));

            return (string) ($criterion['label'] ?? $criteria->last()['label'] ?? 'Без оценки');
        }

        if ($maxScore <= 0) {
            return 'Без оценки';
        }

        $percentage = ($score / $maxScore) * 100;

        return match (true) {
            $percentage >= 90 => '5 (Отлично)',
            $percentage >= 75 => '4 (Хорошо)',
            $percentage >= 60 => '3 (Удовлетворительно)',
            default => '2 (Нужно доработать)',
        };
    }

    private function syncGradebookFromAttempt(ElectronicTestAttempt $attempt): void
    {
        if (!$attempt->studentGroup || !$attempt->group_student_id || !$attempt->assigned_grade_date || trim((string) $attempt->assigned_grade_value) === '') {
            return;
        }

        $this->studentGradeService->upsertManualEntry($attempt->studentGroup, [
            'group_student_id' => $attempt->group_student_id,
            'subject_name' => $attempt->test?->subject_display_name ?: $attempt->test?->title ?: 'Без предмета',
            'grade_date' => $attempt->assigned_grade_date->format('Y-m-d'),
            'grade_value' => $attempt->assigned_grade_value,
        ]);
    }

    private function resolveGroupVariantAssignments(Test $test, Collection $students, array $data): array
    {
        $mode = $this->normalizeVariantAssignmentMode($data['variant_assignment_mode'] ?? null);

        if ($mode === 'balanced') {
            $variants = $this->testVariantService->buildBalancedVariantNumbers($test, $students->count());

            return $students->values()->mapWithKeys(fn ($student, $index) => [
                (int) $student->id => (int) ($variants[$index] ?? 1),
            ])->all();
        }

        if ($mode === 'custom') {
            $customAssignments = collect($data['variant_numbers'] ?? [])
                ->mapWithKeys(function ($variantNumber, $studentId) use ($test) {
                    $normalizedStudentId = (int) $studentId;

                    return [
                        $normalizedStudentId => $this->testVariantService->validateVariantNumber(
                            $test,
                            $variantNumber,
                            'variant_numbers.' . $normalizedStudentId
                        ),
                    ];
                })
                ->all();

            $missingStudentIds = $students
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($studentId) => !array_key_exists($studentId, $customAssignments))
                ->values()
                ->all();

            if ($missingStudentIds !== []) {
                throw ValidationException::withMessages([
                    'variant_numbers' => 'Для некоторых учеников не указан номер варианта.',
                ]);
            }

            return $customAssignments;
        }

        $variantNumber = $this->testVariantService->validateVariantNumber(
            $test,
            $data['variant_number'] ?? 1
        );

        return $students->mapWithKeys(fn ($student) => [(int) $student->id => $variantNumber])->all();
    }

    private function resolveDefaultVariantNumber(Test $test, array $data): int
    {
        $mode = $this->normalizeVariantAssignmentMode($data['variant_assignment_mode'] ?? null);

        if ($mode === 'same') {
            return $this->testVariantService->validateVariantNumber($test, $data['variant_number'] ?? 1);
        }

        return 1;
    }

    private function normalizeVariantAssignmentMode(?string $mode): string
    {
        $normalized = trim((string) $mode);

        return in_array($normalized, ['same', 'balanced', 'custom'], true)
            ? $normalized
            : 'same';
    }

    private function normalizeDeliveryMode(?string $mode): string
    {
        $normalized = trim((string) $mode);

        return in_array($normalized, ['blank', 'electronic', 'hybrid'], true)
            ? $normalized
            : 'blank';
    }

    private function buildSessionLink(ElectronicTestSession $session): string
    {
        return url('/take-test/session/' . $session->access_token);
    }

    private function buildMemberLink(ElectronicTestSessionMember $member): string
    {
        return url('/take-test/student/' . $member->access_token);
    }

    private function buildCodeLink(Test $test): string
    {
        return url('/take-test?code=' . urlencode((string) $test->access_code));
    }

    private function generateUniqueTestAccessCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = collect(range(1, 8))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (Test::query()->where('access_code', $code)->exists());

        return $code;
    }

    private function generateUniqueToken(string $modelClass, string $column): string
    {
        do {
            $token = Str::random(48);
        } while ($modelClass::query()->where($column, $token)->exists());

        return $token;
    }
}
