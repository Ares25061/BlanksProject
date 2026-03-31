<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты проверки | BlanksProject</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">

@include('layouts.nav')

<div class="max-w-7xl mx-auto px-4 py-8">
    <div id="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sky-500"></div>
        <p class="text-slate-600 mt-4">Загружаю результаты проверки...</p>
    </div>

    <div id="pageContent" class="hidden space-y-6">
        <section class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
            <div class="flex flex-wrap justify-between items-start gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-sky-700 font-semibold">Разбор бланков</p>
                    <h1 class="text-3xl font-bold mt-2">Результаты проверки</h1>
                    <p id="pageSubtitle" class="text-slate-600 mt-3">Показываю выбранные и правильные варианты ответов, а также итоговую оценку.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button id="backToTestButton" class="hidden bg-sky-600 text-white px-4 py-3 rounded-2xl hover:bg-sky-500 transition">
                        Вернуться к тесту
                    </button>
                    <button onclick="window.history.back()" class="bg-slate-900 text-white px-4 py-3 rounded-2xl hover:bg-slate-800 transition">
                        Назад
                    </button>
                </div>
            </div>
        </section>

        <div id="resultsList" class="space-y-6"></div>
    </div>
</div>

<script>
    const params = new URLSearchParams(window.location.search);
    const blankFormIds = (params.get('ids') || '')
        .split(',')
        .map((value) => parseInt(value.trim(), 10))
        .filter((value) => !Number.isNaN(value) && value > 0);
    const previewTokens = (params.get('preview_tokens') || '')
        .split(',')
        .map((value) => value.trim())
        .filter(Boolean);
    const testId = params.get('test_id');

    async function authorizedFetch(url, options = {}) {
        return authApiFetch(url, options, {
            accept: options.accept || 'application/json'
        });
    }

    async function apiFetch(url, options = {}) {
        return authorizedFetch(url, options);
    }

    async function loadResults() {
        if (!blankFormIds.length && !previewTokens.length) {
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('pageContent').classList.remove('hidden');
            document.getElementById('resultsList').innerHTML = `
                <section class="bg-white border border-dashed border-slate-300 rounded-3xl p-12 text-center text-slate-500">
                    Идентификаторы результатов проверки не переданы.
                </section>
            `;
            return;
        }

        try {
            const blankFormResponses = await Promise.all(blankFormIds.map((id) => apiFetch(`/api/blank-forms/${id}`)));
            const previewResponses = await Promise.all(previewTokens.map((token) => apiFetch(`/api/scan-previews/${encodeURIComponent(token)}`)));
            const responses = [...blankFormResponses, ...previewResponses];
            const failedResponse = responses.find((response) => !response.ok);

            if (failedResponse) {
                throw new Error('Не удалось загрузить один или несколько результатов проверки');
            }

            const [blankFormPayloads, previewPayloads] = await Promise.all([
                Promise.all(blankFormResponses.map((response) => response.json())),
                Promise.all(previewResponses.map((response) => response.json())),
            ]);
            const orderedBlankFormPayloads = blankFormIds
                .map((id) => blankFormPayloads.find((payload) => payload.data?.id === id))
                .filter(Boolean);
            const orderedPreviewPayloads = previewPayloads;
            const orderedPayloads = [...orderedBlankFormPayloads, ...orderedPreviewPayloads];
            const scanPreviewUrls = await Promise.all(orderedPayloads.map(({ data: blankForm }) => fetchScanPreviewUrls(blankForm)));

            const subtitle = orderedPayloads.length > 1
                ? `Показываю выбранные и правильные варианты ответов, а также итоговый результат для ${orderedPayloads.length} бланков.`
                : 'Показываю выбранные и правильные варианты ответов, а также итоговый результат.';
            document.getElementById('pageSubtitle').textContent = subtitle;

            renderResults(orderedPayloads.map((payload, index) => ({
                ...payload,
                scanPreviewUrls: scanPreviewUrls[index]
            })));

            document.getElementById('loading').classList.add('hidden');
            document.getElementById('pageContent').classList.remove('hidden');

            if (testId) {
                const button = document.getElementById('backToTestButton');
                button.classList.remove('hidden');
                button.onclick = () => {
                    window.location.href = `/tests/${testId}#workflow`;
                };
            }
        } catch (error) {
            console.error(error);
            alert(error.message || 'Ошибка загрузки результатов');
        }
    }

    async function fetchScanPreviewUrls(blankForm) {
        const entries = getScanPageEntries(blankForm);
        if (!entries.length) {
            return [];
        }

        const previews = await Promise.all(entries.map(async (entry) => {
            const pageQuery = entry.pageNumber ? `?page=${entry.pageNumber}` : '';
            const baseUrl = blankForm.is_foreign_scan
                ? `/api/scan-previews/${encodeURIComponent(blankForm.preview_token)}/scan-image`
                : `/api/blank-forms/${blankForm.id}/scan-image`;
            const response = await authorizedFetch(`${baseUrl}${pageQuery}`, {
                accept: 'image/*'
            });

            if (!response.ok) {
                return null;
            }

            const blob = await response.blob();

            return {
                ...entry,
                url: URL.createObjectURL(blob),
            };
        }));

        return previews.filter(Boolean);
    }

    function getScanPageEntries(blankForm) {
        const pages = (blankForm?.metadata?.scan?.pages || [])
            .filter((page) => page?.scan_path)
            .map((page, index) => ({
                pageNumber: Number(page.page_number) || (index + 1),
                label: page.question_range?.start && page.question_range?.end
                    ? `Лист ${Number(page.page_number) || (index + 1)} • вопросы ${page.question_range.start}-${page.question_range.end}`
                    : `Лист ${Number(page.page_number) || (index + 1)}`,
            }))
            .sort((left, right) => left.pageNumber - right.pageNumber);

        if (pages.length) {
            return pages;
        }

        if (blankForm?.scan_path) {
            return [{
                pageNumber: 1,
                label: 'Лист 1',
            }];
        }

        return [];
    }

    function renderResults(payloads) {
        document.getElementById('resultsList').innerHTML = payloads.map(({ data: blankForm, grade, scanPreviewUrls }) => {
            const studentName = [blankForm.last_name, blankForm.first_name, blankForm.patronymic].filter(Boolean).join(' ') || 'Без имени';
            const questions = (blankForm.test?.questions || []).slice().sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
            const answerMap = new Map((blankForm.student_answers || []).map((answer) => [answer.question_id, answer]));
            const scanWarnings = blankForm.metadata?.scan?.warnings || [];
            const scanFileName = blankForm.metadata?.scan?.file_name || '';
            const recognizedAnswers = blankForm.metadata?.scan?.recognized_answers || [];
            const variantNumber = blankForm.variant_number || 1;
            const recognizedSummary = recognizedAnswers.length
                ? recognizedAnswers.map((item) => `${item.question_number}: ${formatRecognizedAnswer(item.selected)}`).join(' • ')
                : '';

            return `
                <section class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-200 bg-slate-50">
                        <div class="flex flex-wrap justify-between gap-4">
                            <div>
                                <p class="text-sm uppercase tracking-[0.25em] text-sky-700 font-semibold">${escapeHtml(blankForm.test?.title || 'Тест')}</p>
                                <h2 class="text-2xl font-bold mt-2">${escapeHtml(studentName)}</h2>
                                <div class="text-slate-500 mt-2">${escapeHtml(blankForm.group_name || 'Группа не указана')} • Вариант ${escapeHtml(String(variantNumber))} • ${escapeHtml(blankForm.form_number || '')}</div>
                                ${blankForm.is_foreign_scan ? '<div class="mt-2 inline-flex items-center gap-2 rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">Чужой бланк • только OCR-разбор</div>' : ''}
                            </div>
                            <div class="grid sm:grid-cols-3 gap-3 min-w-[280px]">
                                <div class="bg-white border border-slate-200 rounded-2xl px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Баллы</div>
                                    <div class="text-xl font-bold mt-2">${grade?.score ?? blankForm.total_score ?? 0} / ${grade?.max_score ?? 0}</div>
                                </div>
                                <div class="bg-white border border-slate-200 rounded-2xl px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Оценка</div>
                                    <div class="text-xl font-bold mt-2">${escapeHtml(grade?.grade || blankForm.grade_label || '—')}</div>
                                </div>
                                <div class="bg-white border border-slate-200 rounded-2xl px-4 py-3">
                                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Процент</div>
                                    <div class="text-xl font-bold mt-2">${grade?.percentage ?? 0}%</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 border-b border-slate-200 bg-slate-50">
                        ${renderAssignedGradePanel(blankForm, grade)}
                    </div>

                    ${(scanPreviewUrls.length || recognizedSummary) ? `
                        <div class="p-6 border-b border-slate-200 bg-white">
                            <div class="grid xl:grid-cols-[0.95fr_1.05fr] gap-6 items-start">
                                <div>
                                    <div class="text-sm uppercase tracking-[0.25em] text-slate-400 mb-3">Скан бланка</div>
                                    ${scanPreviewUrls.length ? `
                                        <div class="space-y-4">
                                            ${scanPreviewUrls.map((page) => `
                                                <div class="rounded-3xl overflow-hidden border border-slate-200 bg-slate-100">
                                                    <div class="px-4 py-3 border-b border-slate-200 bg-white text-sm font-medium text-slate-700">${escapeHtml(page.label)}</div>
                                                    <img src="${page.url}" alt="Скан бланка ${escapeHtml(studentName)} стр. ${page.pageNumber}" class="w-full h-auto block">
                                                </div>
                                            `).join('')}
                                        </div>
                                    ` : `
                                        <div class="rounded-3xl border border-dashed border-slate-300 text-slate-500 p-8 text-center">
                                            Изображение скана не найдено.
                                        </div>
                                    `}
                                </div>

                                <div class="space-y-4">
                                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                        <div class="text-sm uppercase tracking-[0.25em] text-slate-400 mb-3">Распознано со скана</div>
                                        <div class="font-semibold text-slate-900">${recognizedSummary || 'Данные распознавания не сохранены.'}</div>
                                    </div>
                                    <div class="rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
                                        Ниже показан уже проверенный результат по каждому вопросу. Скан сверху помогает быстро сверить, где ученик реально поставил отметку.
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : ''}

                    ${scanWarnings.length ? `
                        <div class="px-6 py-4 border-b border-amber-200 bg-amber-50 text-amber-900">
                            <div class="font-semibold">Предупреждения распознавания</div>
                            <div class="text-sm mt-2">${scanWarnings.map((warning) => escapeHtml(warning)).join('<br>')}</div>
                        </div>
                    ` : ''}

                    <div class="divide-y divide-slate-200">
                        ${questions.map((question, index) => {
                            const studentAnswer = answerMap.get(question.id);
                            const selectedIds = normalizeSelectedAnswerIds(studentAnswer);
                            const answers = question.variant_answers || question.answers || [];
                            const selectedAnswers = answers.filter((answer) => selectedIds.includes(answer.id));
                            const correctAnswers = answers.filter((answer) => answer.is_correct);
                            const selectedLabel = selectedAnswers.length ? selectedAnswers.map((answer, answerIndex) => buildAnswerBadge(answer, answers)).join(', ') : 'Нет ответа';
                            const correctLabel = correctAnswers.length ? correctAnswers.map((answer) => buildAnswerBadge(answer, answers)).join(', ') : 'Не задан';
                            const questionState = getQuestionState(studentAnswer, selectedIds);

                            return `
                                <article class="p-6">
                                    <div class="flex flex-wrap justify-between gap-4 mb-4">
                                        <div>
                                            <div class="text-sm text-slate-500">Вопрос ${index + 1}</div>
                                            <h3 class="text-lg font-semibold text-slate-900 mt-1">${escapeHtml(question.question_text)}</h3>
                                        </div>
                                        <div class="flex items-start gap-3">
                                            <span class="px-3 py-1 rounded-full text-sm ${questionState.className}">
                                                ${questionState.label}
                                            </span>
                                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm">
                                                ${studentAnswer?.points_earned ?? 0} / ${question.points || 0} балл.
                                            </span>
                                        </div>
                                    </div>

                                    <div class="grid lg:grid-cols-[1fr_1fr] gap-4 mb-4">
                                        <div class="rounded-2xl border border-slate-200 p-4 bg-slate-50">
                                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 mb-2">Выбранный вариант</div>
                                            <div class="font-semibold text-slate-900">${selectedLabel}</div>
                                        </div>
                                        <div class="rounded-2xl border border-emerald-200 p-4 bg-emerald-50">
                                            <div class="text-xs uppercase tracking-[0.25em] text-emerald-700 mb-2">Правильный вариант</div>
                                            <div class="font-semibold text-emerald-900">${correctLabel}</div>
                                        </div>
                                    </div>

                                    <div class="grid gap-2">
                                        ${answers.map((answer) => {
                                            const isSelected = selectedIds.includes(answer.id);
                                            const isCorrect = Boolean(answer.is_correct);
                                            return `
                                                <div class="rounded-2xl border px-4 py-3 flex flex-wrap justify-between gap-3 ${isCorrect ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white'}">
                                                    <div class="flex items-start gap-3">
                                                        <span class="font-semibold">${escapeHtml(getAnswerLetter(answer, answers))}.</span>
                                                        <span>${escapeHtml(answer.answer_text)}</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-sm">
                                                        ${isSelected ? '<span class="px-2 py-1 rounded-full bg-sky-100 text-sky-700">Выбран</span>' : ''}
                                                        ${isCorrect ? '<span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Правильный</span>' : ''}
                                                    </div>
                                                </div>
                                            `;
                                        }).join('')}
                                    </div>
                                </article>
                            `;
                        }).join('')}
                    </div>

                    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 text-sm text-slate-500">
                        ${scanFileName ? `Источник скана: ${escapeHtml(scanFileName)}` : 'Источник скана не сохранен'}
                    </div>
                </section>
            `;
        }).join('');
    }

    function renderAssignedGradePanel(blankForm, grade) {
        if (blankForm.is_foreign_scan) {
            return `
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-5 text-slate-500">
                    Это чужой бланк или временный OCR-разбор. Его можно просмотреть и сравнить со сканом, но поставить оценку в журнал нельзя.
                </div>
            `;
        }

        if (!blankForm.group_student_id) {
            return `
                <div class="flex flex-wrap justify-between gap-4 items-start">
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-5 text-slate-500 flex-1">
                        Этот бланк не привязан к студенту учебной группы, поэтому оценку в журнал ученика поставить нельзя.
                    </div>
                    <button onclick="deleteCheckedBlankForm(${blankForm.id})" class="bg-rose-600 text-white px-4 py-3 rounded-2xl hover:bg-rose-500 transition text-sm">
                        Удалить работу
                    </button>
                </div>
            `;
        }

        const currentGrade = blankForm.assigned_grade_value
            ? `<div class="text-sm text-slate-500 mt-3">Сейчас в журнале: <span class="font-semibold text-slate-900">${escapeHtml(blankForm.assigned_grade_value)}</span> от ${formatDate(blankForm.assigned_grade_date)}${blankForm.grade_assigner ? ` • поставил(а) ${escapeHtml(blankForm.grade_assigner.name)}` : ''}</div>`
            : '<div class="text-sm text-slate-500 mt-3">Оценка в журнал по этому бланку еще не поставлена.</div>';

        return `
            <div class="flex flex-wrap justify-between gap-4 items-start">
                <div>
                    <div class="text-sm uppercase tracking-[0.25em] text-slate-400">Оценка ученику</div>
                    <div class="text-lg font-semibold text-slate-900 mt-2">Сохранить результат в журнал по дате</div>
                    ${currentGrade}
                </div>
                <button onclick="deleteCheckedBlankForm(${blankForm.id})" class="bg-rose-600 text-white px-4 py-3 rounded-2xl hover:bg-rose-500 transition text-sm">
                    Удалить работу
                </button>
            </div>

            <div class="grid md:grid-cols-[220px_220px_auto] gap-3 mt-5">
                <input id="assignedGradeValue-${blankForm.id}" type="text" value="${escapeAttribute(blankForm.assigned_grade_value || suggestAssignedGrade(blankForm, grade))}"
                       class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                       placeholder="Например: 5">
                <input id="assignedGradeDate-${blankForm.id}" type="date" value="${escapeAttribute(defaultAssignedDate(blankForm))}"
                       class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                <button onclick="saveAssignedGrade(${blankForm.id})" class="bg-sky-600 text-white px-5 py-3 rounded-2xl hover:bg-sky-500 transition font-medium">
                    Поставить оценку
                </button>
            </div>
        `;
    }

    async function saveAssignedGrade(blankFormId) {
        const gradeInput = document.getElementById(`assignedGradeValue-${blankFormId}`);
        const dateInput = document.getElementById(`assignedGradeDate-${blankFormId}`);
        const gradeValue = gradeInput?.value?.trim();
        const gradeDate = dateInput?.value;

        if (!gradeValue) {
            alert('Укажите оценку');
            gradeInput?.focus();
            return;
        }

        if (!gradeDate) {
            alert('Укажите дату оценки');
            dateInput?.focus();
            return;
        }

        try {
            const response = await apiFetch(`/api/blank-forms/${blankFormId}/assign-grade`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    grade_value: gradeValue,
                    grade_date: gradeDate
                })
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || 'Не удалось сохранить оценку');
            }

            await loadResults();
        } catch (error) {
            alert(error.message || 'Ошибка сохранения оценки');
        }
    }

    async function deleteCheckedBlankForm(blankFormId) {
        if (!confirm('Удалить эту проверенную работу? Вместе с ней пропадет и поставленная по ней оценка в журнале.')) {
            return;
        }

        try {
            const response = await apiFetch(`/api/blank-forms/${blankFormId}`, {
                method: 'DELETE'
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || 'Не удалось удалить работу');
            }

            const nextIds = blankFormIds.filter((id) => id !== blankFormId);
            const params = new URLSearchParams(window.location.search);

            if (!nextIds.length) {
                if (testId) {
                    window.location.href = `/tests/${testId}#workflow`;
                    return;
                }

                window.location.href = '/tests';
                return;
            }

            params.set('ids', nextIds.join(','));
            window.location.search = params.toString();
        } catch (error) {
            alert(error.message || 'Ошибка удаления работы');
        }
    }

    function getQuestionState(studentAnswer, selectedIds) {
        if (!studentAnswer || !selectedIds.length) {
            return {
                label: 'Нет ответа',
                className: 'bg-slate-100 text-slate-700'
            };
        }

        if (studentAnswer.is_correct) {
            return {
                label: 'Верно',
                className: 'bg-emerald-100 text-emerald-800'
            };
        }

        return {
            label: 'Неверно',
            className: 'bg-rose-100 text-rose-800'
        };
    }

    function normalizeSelectedAnswerIds(studentAnswer) {
        if (!studentAnswer) {
            return [];
        }

        if (Array.isArray(studentAnswer.selected_answers) && studentAnswer.selected_answers.length) {
            return studentAnswer.selected_answers.map((id) => Number(id));
        }

        if (studentAnswer.answer_id) {
            return [Number(studentAnswer.answer_id)];
        }

        return [];
    }

    function getAnswerLetter(answer, answers) {
        const index = answers.findIndex((item) => item.id === answer.id);
        return String.fromCharCode(65 + Math.max(index, 0));
    }

    function buildAnswerBadge(answer, answers) {
        return `${escapeHtml(getAnswerLetter(answer, answers))} (${escapeHtml(answer.answer_text)})`;
    }

    function formatRecognizedAnswer(selected) {
        if (!Array.isArray(selected) || !selected.length) {
            return 'нет ответа';
        }

        return selected.join('+');
    }

    function suggestAssignedGrade(blankForm, grade) {
        const source = blankForm?.assigned_grade_value || grade?.grade || blankForm?.grade_label || '';
        const trimmed = source.trim();
        if (!trimmed) {
            return '';
        }

        const numericMatch = trimmed.match(/^\d+/);
        return numericMatch ? numericMatch[0] : trimmed;
    }

    function defaultAssignedDate(blankForm) {
        if (blankForm?.assigned_grade_date) {
            return normalizeDateValue(blankForm.assigned_grade_date);
        }

        if (blankForm?.submission_date) {
            return normalizeDateValue(blankForm.submission_date);
        }

        return new Date().toISOString().slice(0, 10);
    }

    function normalizeDateValue(value) {
        if (!value) {
            return '';
        }

        if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return value;
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }

        return date.toISOString().slice(0, 10);
    }

    function formatDate(value) {
        const normalized = normalizeDateValue(value);
        if (!normalized) {
            return 'Без даты';
        }

        return new Date(`${normalized}T00:00:00`).toLocaleDateString('ru-RU');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function escapeAttribute(text) {
        return escapeHtml(text).replace(/"/g, '&quot;');
    }

    document.addEventListener('DOMContentLoaded', async () => {
        if (!await ensureAuthenticatedPage()) {
            return;
        }

        loadResults();
    });
</script>
</body>
</html>
