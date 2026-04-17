<!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Пройти тест'])
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<main class="container mx-auto max-w-6xl px-4 py-8">
    <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/90">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700">Электронное тестирование</p>
                <h1 class="mt-2 text-3xl font-bold">Пройти тест</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
                    Введите код теста или откройте прямую ссылку от преподавателя. Регистрация на сайте не требуется.
                </p>
            </div>
            <div id="sessionStatusPill" class="hidden rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200"></div>
        </div>
    </section>

    <div class="mt-6 space-y-6">
            <section id="lookupSection" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[220px] flex-1">
                        <label for="testCodeInput" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Код теста</label>
                        <input id="testCodeInput" type="text" maxlength="20" class="w-full rounded-2xl border border-slate-300 px-4 py-3 uppercase tracking-[0.28em] focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 dark:border-slate-700" placeholder="Например ABCD2345">
                    </div>
                    <button id="resolveCodeButton" type="button" class="rounded-2xl bg-emerald-600 px-5 py-3 font-medium text-white transition hover:bg-emerald-500">
                        Открыть тест
                    </button>
                </div>
                <div id="pageMessage" class="mt-4 hidden rounded-2xl border px-4 py-3 text-sm"></div>
            </section>

            <section id="sessionSection" class="hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap justify-between gap-4">
                    <div>
                        <p id="sessionModeLabel" class="text-sm font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400"></p>
                        <h2 id="sessionTitle" class="mt-2 text-3xl font-bold"></h2>
                        <p id="sessionMeta" class="mt-2 text-sm text-slate-600 dark:text-slate-300"></p>
                        <p id="sessionDescription" class="mt-3 max-w-3xl text-sm text-slate-500 dark:text-slate-400"></p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm dark:border-slate-700 dark:bg-slate-950/60">
                        <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Требования</div>
                        <div class="mt-2 font-medium">Полный экран и журнал активности</div>
                        <div class="mt-1 text-slate-500 dark:text-slate-400">При выходе из окна и разворачивании фиксируются события прохождения.</div>
                    </div>
                </div>

                <div id="studentPickerSection" class="mt-6 space-y-4">
                    <div id="prefilledStudentCard" class="hidden rounded-3xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                        <div class="text-xs uppercase tracking-[0.25em] text-emerald-700 dark:text-emerald-300">Персональная ссылка</div>
                        <div id="prefilledStudentName" class="mt-2 text-xl font-semibold"></div>
                    </div>

                    <div id="studentListSection" class="space-y-4">
                        <div>
                            <label for="studentSelect" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Ученик</label>
                            <select id="studentSelect" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 dark:border-slate-700"></select>
                        </div>
                        <div id="manualNameWrap" class="hidden">
                            <label for="manualFullName" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">ФИО вручную</label>
                            <input id="manualFullName" type="text" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/30 dark:border-slate-700" placeholder="Введите фамилию, имя и отчество">
                        </div>
                    </div>

                    <button id="startAttemptButton" type="button" class="rounded-2xl bg-indigo-600 px-5 py-3 font-medium text-white transition hover:bg-indigo-500">
                        Начать тест
                    </button>
                </div>
            </section>

            <section id="attemptSection" class="hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-indigo-600 dark:text-indigo-300">Прохождение</p>
                        <h2 id="attemptStudentTitle" class="mt-2 text-3xl font-bold"></h2>
                        <p id="attemptIntro" class="mt-2 text-sm text-slate-600 dark:text-slate-300"></p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button id="fullscreenButton" type="button" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                            Полный экран
                        </button>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-950/60">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Осталось времени</div>
                            <div id="attemptTimer" class="mt-1 text-xl font-semibold">Без лимита</div>
                        </div>
                    </div>
                </div>

                <div id="attemptQuestions" class="mt-6 space-y-4"></div>

                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <button id="submitAttemptButton" type="button" class="rounded-2xl bg-emerald-600 px-5 py-3 font-medium text-white transition hover:bg-emerald-500">
                        Сдать тест
                    </button>
                </div>
            </section>

            <section id="finishSection" class="hidden rounded-3xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm dark:border-emerald-900/40 dark:bg-emerald-950/30">
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-emerald-700 dark:text-emerald-300">Работа отправлена</p>
                <h2 id="finishStudentName" class="mt-2 text-3xl font-bold"></h2>
                <p class="mt-3 text-sm text-emerald-900 dark:text-emerald-100">Преподаватель получил уведомление о завершении теста и сможет проверить ответы и выставить оценку.</p>
            </section>
    </div>
</main>

<script>
    const embeddedSessionToken = @json($sessionToken);
    const embeddedMemberToken = @json($memberToken);
    let currentSession = null;
    let currentAttempt = null;
    let antiCheatBound = false;
    let antiCheatActive = false;
    let timerHandle = null;

    function setPageMessage(message = '', type = 'info') {
        const box = document.getElementById('pageMessage');
        if (!message) {
            box.classList.add('hidden');
            box.textContent = '';
            box.className = 'mt-4 hidden rounded-2xl border px-4 py-3 text-sm';
            return;
        }

        box.className = `mt-4 rounded-2xl border px-4 py-3 text-sm ${
            type === 'error'
                ? 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200'
                : type === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200'
                    : 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-200'
        }`;
        box.textContent = message;
    }

    async function publicApiFetch(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.headers || {}),
            },
        });

        return response;
    }

    async function parseApiResponse(response) {
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationErrors = payload.errors ? Object.values(payload.errors).flat().join(', ') : '';
            throw new Error(validationErrors || payload.message || 'Не удалось выполнить запрос.');
        }

        return payload.data || payload;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function formatDateTime(value) {
        if (!value) {
            return '';
        }

        return new Date(value).toLocaleString('ru-RU');
    }

    function formatDuration(totalSeconds) {
        const safe = Math.max(0, Math.floor(totalSeconds));
        const hours = String(Math.floor(safe / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((safe % 3600) / 60)).padStart(2, '0');
        const seconds = String(safe % 60).padStart(2, '0');

        return `${hours}:${minutes}:${seconds}`;
    }

    function renderSession(payload) {
        currentSession = payload.session;
        if (embeddedSessionToken || embeddedMemberToken) {
            document.getElementById('lookupSection').classList.add('hidden');
        }
        document.getElementById('sessionSection').classList.remove('hidden');
        document.getElementById('sessionModeLabel').textContent = currentSession.delivery_mode_label;
        document.getElementById('sessionTitle').textContent = currentSession.title || 'Тест';
        document.getElementById('sessionMeta').textContent = [
            currentSession.subject_name,
            currentSession.group?.name ? `Группа: ${currentSession.group.name}` : '',
            currentSession.time_limit ? `Лимит: ${currentSession.time_limit} мин.` : 'Без ограничения по времени',
        ].filter(Boolean).join(' • ');
        document.getElementById('sessionDescription').textContent = currentSession.description || 'Описание теста не заполнено.';
        document.getElementById('sessionStatusPill').classList.remove('hidden');
        document.getElementById('sessionStatusPill').textContent = currentSession.access_code ? `Код: ${currentSession.access_code}` : 'Тест открыт';

        const prefilledCard = document.getElementById('prefilledStudentCard');
        const studentListSection = document.getElementById('studentListSection');
        if (currentSession.prefilled_student) {
            prefilledCard.classList.remove('hidden');
            studentListSection.classList.add('hidden');
            document.getElementById('prefilledStudentName').textContent = currentSession.prefilled_student.full_name || 'Ученик';
        } else {
            prefilledCard.classList.add('hidden');
            studentListSection.classList.remove('hidden');
            const studentSelect = document.getElementById('studentSelect');
            studentSelect.innerHTML = `
                <option value="">Выберите себя из списка</option>
                ${(currentSession.students || []).map((student) => `
                    <option value="${student.id}">${escapeHtml(student.full_name)}</option>
                `).join('')}
                <option value="manual">Меня нет в списке</option>
            `;
            toggleManualStudentInput();
        }
    }

    function toggleManualStudentInput() {
        const wrap = document.getElementById('manualNameWrap');
        const studentSelect = document.getElementById('studentSelect');
        wrap.classList.toggle('hidden', studentSelect.value !== 'manual');
    }

    async function resolveCode() {
        const code = document.getElementById('testCodeInput').value.trim().toUpperCase();
        if (!code) {
            setPageMessage('Введите код теста.', 'error');
            return;
        }

        try {
            setPageMessage('Открываю тест...', 'info');
            const response = await publicApiFetch('/api/public/test-code/resolve', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ code }),
            });
            const data = await parseApiResponse(response);
            renderSession(data);
            setPageMessage('Тест найден. Выберите себя и начните прохождение.', 'success');
        } catch (error) {
            setPageMessage(error.message, 'error');
        }
    }

    async function loadEmbeddedRoute() {
        try {
            if (embeddedMemberToken) {
                const response = await publicApiFetch(`/api/public/electronic-members/${embeddedMemberToken}`);
                renderSession(await parseApiResponse(response));
                return;
            }

            if (embeddedSessionToken) {
                const response = await publicApiFetch(`/api/public/electronic-sessions/${embeddedSessionToken}`);
                renderSession(await parseApiResponse(response));
                return;
            }

            const codeFromQuery = new URLSearchParams(window.location.search).get('code');
            if (codeFromQuery) {
                document.getElementById('testCodeInput').value = codeFromQuery.toUpperCase();
                await resolveCode();
            }
        } catch (error) {
            setPageMessage(error.message, 'error');
        }
    }

    async function startAttempt() {
        if (!currentSession) {
            setPageMessage('Сначала откройте тест по коду или ссылке.', 'error');
            return;
        }

        const endpoint = embeddedMemberToken
            ? `/api/public/electronic-members/${embeddedMemberToken}/start`
            : `/api/public/electronic-sessions/${currentSession.token}/start`;
        const payload = embeddedMemberToken ? {} : {
            group_student_id: document.getElementById('studentSelect').value && document.getElementById('studentSelect').value !== 'manual'
                ? parseInt(document.getElementById('studentSelect').value, 10)
                : null,
            manual_full_name: document.getElementById('studentSelect').value === 'manual'
                ? document.getElementById('manualFullName').value.trim()
                : null,
        };

        try {
            const response = await publicApiFetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const data = await parseApiResponse(response);
            renderAttempt(data.attempt, data.session);
            setPageMessage('');
        } catch (error) {
            setPageMessage(error.message, 'error');
        }
    }

    function renderAttempt(attempt, sessionInfo = null) {
        currentAttempt = attempt;
        antiCheatActive = true;
        document.getElementById('lookupSection').classList.add('hidden');
        document.getElementById('sessionSection').classList.add('hidden');
        document.getElementById('finishSection').classList.add('hidden');
        document.getElementById('attemptSection').classList.remove('hidden');
        document.getElementById('attemptStudentTitle').textContent = attempt.student_full_name || 'Ученик';
        document.getElementById('attemptIntro').textContent = [
            sessionInfo?.title || currentSession?.title,
            attempt.group_name ? `Группа: ${attempt.group_name}` : '',
            `Вариант ${attempt.variant_number}`,
        ].filter(Boolean).join(' • ');

        const questionsWrap = document.getElementById('attemptQuestions');
        questionsWrap.innerHTML = (attempt.questions || []).map((question, index) => `
            <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-950/50" data-question-id="${question.id}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Вопрос ${index + 1}</div>
                        <h3 class="mt-2 text-lg font-semibold">${escapeHtml(question.question_text)}</h3>
                    </div>
                    <div class="text-sm text-slate-500 dark:text-slate-400">${question.type === 'multiple' ? 'Несколько ответов' : 'Один ответ'}</div>
                </div>
                <div class="mt-4 space-y-3">
                    ${(question.answers || []).map((answer) => `
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 transition hover:border-emerald-300 dark:border-slate-700 dark:bg-slate-900">
                            <input
                                type="${question.type === 'multiple' ? 'checkbox' : 'radio'}"
                                name="question_${question.id}"
                                value="${answer.id}"
                                ${(question.selected_answers || []).includes(answer.id) ? 'checked' : ''}
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                            >
                            <span>${escapeHtml(answer.answer_text)}</span>
                        </label>
                    `).join('')}
                </div>
            </article>
        `).join('');

        bindAntiCheat();
        requestFullscreenMode();
        startTimer();
    }

    function collectAttemptAnswers() {
        const payload = {};

        document.querySelectorAll('#attemptQuestions [data-question-id]').forEach((questionCard) => {
            const questionId = questionCard.dataset.questionId;
            const selected = Array.from(questionCard.querySelectorAll('input:checked'))
                .map((input) => parseInt(input.value, 10))
                .filter((value) => Number.isInteger(value) && value > 0);

            payload[questionId] = selected;
        });

        return payload;
    }

    async function submitAttempt() {
        if (!currentAttempt?.token) {
            return;
        }

        if (!confirm('Отправить работу преподавателю? После этого изменить ответы уже нельзя.')) {
            return;
        }

        try {
            const response = await publicApiFetch(`/api/public/electronic-attempts/${currentAttempt.token}/submit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    answers: collectAttemptAnswers(),
                }),
            });
            const data = await parseApiResponse(response);
            antiCheatActive = false;
            finishAttempt(data);
        } catch (error) {
            setPageMessage(error.message, 'error');
        }
    }

    function finishAttempt(data) {
        antiCheatActive = false;
        clearInterval(timerHandle);
        document.getElementById('attemptSection').classList.add('hidden');
        document.getElementById('finishSection').classList.remove('hidden');
        document.getElementById('finishStudentName').textContent = data.student_full_name || 'Работа отправлена';
        setPageMessage(`Работа отправлена ${formatDateTime(data.submitted_at)}.`, 'success');
    }

    function startTimer() {
        clearInterval(timerHandle);

        if (!currentAttempt?.time_limit || !currentAttempt?.started_at) {
            document.getElementById('attemptTimer').textContent = 'Без лимита';
            return;
        }

        const startedAt = new Date(currentAttempt.started_at).getTime();
        const finishAt = startedAt + (currentAttempt.time_limit * 60 * 1000);

        const updateTimer = () => {
            const remainingMs = finishAt - Date.now();
            if (remainingMs <= 0) {
                document.getElementById('attemptTimer').textContent = '00:00:00';
                clearInterval(timerHandle);
                submitAttempt();
                return;
            }

            document.getElementById('attemptTimer').textContent = formatDuration(remainingMs / 1000);
        };

        updateTimer();
        timerHandle = setInterval(updateTimer, 1000);
    }

    async function requestFullscreenMode() {
        try {
            if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
                await document.documentElement.requestFullscreen();
            }
        } catch (error) {
            logAntiCheatEvent('fullscreen_denied', {
                message: error.message,
            });
        }
    }

    async function logAntiCheatEvent(eventType, payload = {}) {
        if (!antiCheatActive || !currentAttempt?.token) {
            return;
        }

        try {
            await publicApiFetch(`/api/public/electronic-attempts/${currentAttempt.token}/logs`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    event_type: eventType,
                    payload,
                    occurred_at: new Date().toISOString(),
                }),
            });
        } catch (error) {
            console.error('Не удалось сохранить событие журнала:', error);
        }
    }

    function bindAntiCheat() {
        if (antiCheatBound) {
            return;
        }

        antiCheatBound = true;

        window.addEventListener('blur', () => {
            logAntiCheatEvent('window_blur');
        });

        window.addEventListener('focus', () => {
            logAntiCheatEvent('window_focus');
        });

        document.addEventListener('visibilitychange', () => {
            logAntiCheatEvent(document.hidden ? 'visibility_hidden' : 'visibility_visible');
        });

        document.addEventListener('fullscreenchange', () => {
            logAntiCheatEvent(document.fullscreenElement ? 'fullscreen_enter' : 'fullscreen_exit');
        });

        window.addEventListener('resize', () => {
            logAntiCheatEvent('window_resize', {
                width: window.innerWidth,
                height: window.innerHeight,
                screen_x: window.screenX,
                screen_y: window.screenY,
            });
        });

        document.documentElement.addEventListener('mouseleave', () => {
            logAntiCheatEvent('pointer_leave_page');
        });
    }

    document.getElementById('resolveCodeButton').addEventListener('click', resolveCode);
    document.getElementById('studentSelect')?.addEventListener('change', toggleManualStudentInput);
    document.getElementById('startAttemptButton').addEventListener('click', startAttempt);
    document.getElementById('submitAttemptButton').addEventListener('click', submitAttempt);
    document.getElementById('fullscreenButton').addEventListener('click', requestFullscreenMode);

    document.addEventListener('DOMContentLoaded', async () => {
        await loadEmbeddedRoute();
    });
</script>
</body>
</html>
