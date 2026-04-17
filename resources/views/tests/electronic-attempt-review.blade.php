<!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Проверка электронной работы'])
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<div class="container mx-auto max-w-7xl px-4 py-8">
    <div id="loading" class="py-12 text-center">
        <div class="inline-block h-12 w-12 animate-spin rounded-full border-b-2 border-t-2 border-indigo-500"></div>
        <p class="mt-4 text-slate-600 dark:text-slate-300">Загрузка электронной работы...</p>
    </div>

    <div id="pageContent" class="hidden space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-indigo-700">Проверка электронной работы</p>
                    <h1 id="attemptStudentTitle" class="mt-2 text-3xl font-bold">—</h1>
                    <p id="attemptMeta" class="mt-2 text-sm text-slate-600 dark:text-slate-300">—</p>
                    <p id="attemptStatusText" class="mt-3 text-sm text-slate-500 dark:text-slate-400">—</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a id="backToTestLink" href="/tests" class="rounded-2xl bg-slate-700 px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700">
                        Вернуться к тесту
                    </a>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Статус</div>
                    <div id="statusCard" class="mt-2 text-xl font-bold">—</div>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Вариант</div>
                    <div id="variantCard" class="mt-2 text-xl font-bold">—</div>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Автооценка</div>
                    <div id="autoGradeCard" class="mt-2 text-xl font-bold">—</div>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Баллы</div>
                    <div id="scoreCard" class="mt-2 text-xl font-bold">—</div>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Оценка в журнал</div>
                    <div id="assignedGradeCard" class="mt-2 text-xl font-bold">—</div>
                </div>
            </div>
        </section>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
            <div class="space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold">Ответы ученика</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Разбор выбранных вариантов и начисленных баллов.</p>
                        </div>
                    </div>
                    <div id="answersList" class="mt-5 space-y-3"></div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold">Журнал активности</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">События антисписывания и действия во время прохождения.</p>
                        </div>
                    </div>
                    <div id="logSummaryList" class="mt-5 flex flex-wrap gap-2"></div>
                    <div id="logsList" class="mt-5 space-y-3"></div>
                </section>
            </div>

            <aside class="space-y-6 xl:sticky xl:top-24">
                <section class="proverium-panel rounded-3xl border border-slate-200 p-5 dark:border-slate-800">
                    <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Проверка</div>
                    <div class="mt-4 space-y-4">
                        <div id="successMessage" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200"></div>
                        <div id="errorMessage" class="hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200"></div>

                        <div id="studentBindingWrap" class="hidden">
                            <label for="studentFullName" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">ФИО ученика</label>
                            <input id="studentFullName" type="text" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700" placeholder="Введите ФИО">
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Если ученика не было в группе, он будет добавлен при сохранении.</p>
                        </div>

                        <div>
                            <label for="gradeValue" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Оценка</label>
                            <input id="gradeValue" type="text" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700" placeholder="Например 5">
                        </div>

                        <div>
                            <label for="gradeDate" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Дата оценки</label>
                            <input id="gradeDate" type="date" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-slate-700">
                        </div>

                        <button id="saveReviewButton" type="button" class="w-full rounded-2xl bg-indigo-600 px-5 py-3 font-medium text-white transition hover:bg-indigo-500">
                            Сохранить результат
                        </button>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Контекст</div>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Тест</div>
                            <div id="contextTestTitle" class="mt-2 font-semibold text-slate-900 dark:text-white">—</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Группа</div>
                            <div id="contextGroupTitle" class="mt-2 font-semibold text-slate-900 dark:text-white">—</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Отправлено</div>
                            <div id="contextSubmittedAt" class="mt-2 font-semibold text-slate-900 dark:text-white">—</div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>

    <div id="errorContent" class="hidden rounded-2xl border border-red-300 bg-red-50 px-4 py-3 text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
        Не удалось открыть электронную работу.
    </div>
</div>

<script>
    const attemptId = {{ $attemptId }};
    let currentReview = null;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function formatDateTime(value) {
        if (!value) {
            return 'Не указано';
        }

        return new Date(value).toLocaleString('ru-RU');
    }

    function setMessage(type, message = '') {
        const success = document.getElementById('successMessage');
        const error = document.getElementById('errorMessage');
        success.classList.add('hidden');
        error.classList.add('hidden');

        if (!message) {
            return;
        }

        if (type === 'success') {
            success.textContent = message;
            success.classList.remove('hidden');
            return;
        }

        error.textContent = message;
        error.classList.remove('hidden');
    }

    function showErrorState(message = 'Не удалось открыть электронную работу.') {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('pageContent').classList.add('hidden');
        const errorContent = document.getElementById('errorContent');
        errorContent.textContent = message;
        errorContent.classList.remove('hidden');
    }

    function renderReview(data) {
        currentReview = data;
        const attempt = data.attempt || {};
        const test = data.test || {};
        const group = data.session?.group || null;
        const hasBoundStudent = Boolean(attempt.group_student_id);

        document.getElementById('attemptStudentTitle').textContent = attempt.student_full_name || 'Электронная работа';
        document.getElementById('attemptMeta').textContent = [
            test.title || 'Тест',
            test.subject_name || '',
            group?.name ? `Группа: ${group.name}` : '',
            `Вариант ${attempt.variant_number || 1}`,
        ].filter(Boolean).join(' • ');
        document.getElementById('attemptStatusText').textContent = hasBoundStudent
            ? 'Работа уже привязана к ученику группы.'
            : 'Работа пока не привязана к ученику группы. При необходимости можно добавить ученика прямо отсюда.';

        document.getElementById('statusCard').textContent = attempt.status_label || '—';
        document.getElementById('variantCard').textContent = `Вариант ${attempt.variant_number || 1}`;
        document.getElementById('autoGradeCard').textContent = attempt.grade_label || 'Без оценки';
        document.getElementById('scoreCard').textContent = attempt.total_score ?? '—';
        document.getElementById('assignedGradeCard').textContent = attempt.assigned_grade_value || 'Не выставлена';

        document.getElementById('contextTestTitle').textContent = test.title || '—';
        document.getElementById('contextGroupTitle').textContent = group?.name || 'Не указана';
        document.getElementById('contextSubmittedAt').textContent = formatDateTime(attempt.submitted_at);
        document.getElementById('backToTestLink').href = test.id ? `/tests/${test.id}` : '/tests';

        const answersList = document.getElementById('answersList');
        answersList.innerHTML = (attempt.answers || []).length
            ? attempt.answers.map((answer) => `
                <article class="rounded-2xl border ${answer.is_correct ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20' : 'border-rose-200 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/20'} p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">${escapeHtml(answer.question_text || 'Вопрос')}</div>
                            <div class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                Выбрано: ${(answer.selected_answer_texts || []).map((text) => escapeHtml(text)).join(', ') || '—'}
                            </div>
                        </div>
                        <div class="text-sm font-semibold ${answer.is_correct ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'}">
                            ${answer.points_earned} / ${answer.points}
                        </div>
                    </div>
                </article>
            `).join('')
            : '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-400">Ответы пока не сохранены.</div>';

        const logSummaryList = document.getElementById('logSummaryList');
        logSummaryList.innerHTML = (attempt.log_summary_items || []).length
            ? attempt.log_summary_items.map((item) => `
                <div class="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    ${escapeHtml(item.label)}: <span class="font-semibold text-slate-900 dark:text-white">${item.count}</span>
                </div>
            `).join('')
            : '<div class="text-sm text-slate-500 dark:text-slate-400">Событий антисписывания не зафиксировано.</div>';

        const logsList = document.getElementById('logsList');
        logsList.innerHTML = (attempt.logs || []).length
            ? attempt.logs.map((log) => `
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/70">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">${escapeHtml(log.event_label || log.event_type)}</div>
                            ${log.payload_summary ? `<div class="mt-1 text-sm text-slate-500 dark:text-slate-400">${escapeHtml(log.payload_summary)}</div>` : ''}
                        </div>
                        <div class="text-sm text-slate-500 dark:text-slate-400">${formatDateTime(log.occurred_at)}</div>
                    </div>
                </article>
            `).join('')
            : '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-400">События не зафиксированы.</div>';

        document.getElementById('studentBindingWrap').classList.toggle('hidden', hasBoundStudent);
        document.getElementById('studentFullName').value = hasBoundStudent ? '' : (attempt.student_full_name || '');
        document.getElementById('gradeValue').value = attempt.assigned_grade_value || '';
        document.getElementById('gradeDate').value = attempt.assigned_grade_date || new Date().toISOString().slice(0, 10);
        document.getElementById('saveReviewButton').textContent = hasBoundStudent
            ? 'Сохранить оценку'
            : 'Добавить ученика и сохранить';

        document.getElementById('loading').classList.add('hidden');
        document.getElementById('pageContent').classList.remove('hidden');
    }

    async function loadReview() {
        try {
            if (!await ensureAuthenticatedPage()) {
                return;
            }

            const response = await authApiFetch(`/api/electronic-attempts/${attemptId}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || 'Не удалось загрузить электронную работу.');
            }

            renderReview((await response.json()).data || {});
        } catch (error) {
            console.error(error);
            showErrorState(error.message || 'Не удалось открыть электронную работу.');
        }
    }

    async function saveReview() {
        if (!currentReview?.attempt) {
            return;
        }

        const attempt = currentReview.attempt;
        const gradeValue = document.getElementById('gradeValue').value.trim();
        const gradeDate = document.getElementById('gradeDate').value;
        const studentFullName = document.getElementById('studentFullName').value.trim();
        const button = document.getElementById('saveReviewButton');

        try {
            setMessage();

            if (attempt.group_student_id) {
                if (!gradeValue || !gradeDate) {
                    throw new Error('Укажите оценку и дату.');
                }
            } else {
                if (!studentFullName) {
                    throw new Error('Укажите ФИО ученика.');
                }

                if ((gradeValue && !gradeDate) || (!gradeValue && gradeDate)) {
                    throw new Error('Если ставите оценку сразу, укажите и оценку, и дату.');
                }
            }

            button.disabled = true;
            button.classList.add('opacity-70', 'cursor-wait');

            const url = attempt.group_student_id
                ? `/api/electronic-attempts/${attempt.id}/assign-grade`
                : `/api/electronic-attempts/${attempt.id}/attach-student`;
            const method = attempt.group_student_id ? 'PATCH' : 'POST';
            const payload = attempt.group_student_id
                ? { grade_value: gradeValue, grade_date: gradeDate }
                : {
                    student_full_name: studentFullName,
                    grade_value: gradeValue || null,
                    grade_date: gradeDate || null,
                };

            const response = await authApiFetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = data.errors
                    ? Object.values(data.errors).flat().join(', ')
                    : (data.message || 'Не удалось сохранить результат.');
                throw new Error(message);
            }

            setMessage('success', data.message || 'Результат сохранён.');
            await loadReview();
        } catch (error) {
            setMessage('error', error.message || 'Не удалось сохранить результат.');
        } finally {
            button.disabled = false;
            button.classList.remove('opacity-70', 'cursor-wait');
        }
    }

    document.getElementById('saveReviewButton').addEventListener('click', saveReview);
    document.addEventListener('DOMContentLoaded', loadReview);
</script>
</body>
</html>
