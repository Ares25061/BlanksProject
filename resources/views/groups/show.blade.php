<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Журнал группы | BlanksProject</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">

@include('layouts.nav')

<div class="max-w-[96rem] mx-auto px-4 py-8">
    <div id="loading" class="text-center py-16">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sky-500"></div>
        <p class="text-slate-600 mt-4">Загружаю журнал группы...</p>
    </div>

    <div id="pageContent" class="hidden space-y-6">
        <section class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
            <div class="flex flex-wrap justify-between items-start gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-sky-700 font-semibold">Журнал группы</p>
                    <h1 id="groupTitle" class="text-3xl font-bold mt-2"></h1>
                    <p id="groupDescription" class="text-slate-600 mt-3 max-w-4xl"></p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button onclick="window.location.href='/groups'" class="bg-white border border-slate-200 text-slate-700 px-4 py-3 rounded-2xl hover:border-sky-300 hover:text-sky-700 transition">
                        Все группы
                    </button>
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4 mt-6">
                <div class="bg-slate-50 rounded-2xl p-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Студентов</div>
                    <div id="studentsCount" class="text-2xl font-bold mt-2">0</div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Показано дат</div>
                    <div id="datesCount" class="text-2xl font-bold mt-2">0</div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Текущий предмет</div>
                    <div id="currentSubjectLabel" class="text-2xl font-bold mt-2">Не выбран</div>
                </div>
            </div>
        </section>

        <section class="bg-white border border-slate-200 rounded-3xl shadow-sm p-6">
            <div class="grid xl:grid-cols-[380px_minmax(0,1fr)] gap-6 items-start">
                <div class="space-y-5">
                    <div>
                        <h2 class="text-xl font-semibold">Настройка журнала</h2>
                        <p class="text-slate-500 mt-2">У одной группы можно вести несколько предметов. Переключайте предмет, добавляйте даты и ставьте оценки прямо в ячейках таблицы.</p>
                    </div>

                    <div>
                        <label for="subjectSelect" class="block text-sm font-medium text-slate-700 mb-2">Предмет из существующих</label>
                        <div class="flex gap-3">
                            <select id="subjectSelect" class="flex-1 px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></select>
                            <button onclick="openSelectedSubject()" class="bg-sky-600 text-white px-4 py-3 rounded-2xl hover:bg-sky-500 transition">
                                Открыть
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="newSubjectInput" class="block text-sm font-medium text-slate-700 mb-2">Новый предмет</label>
                        <div class="flex gap-3">
                            <input id="newSubjectInput" type="text"
                                   class="flex-1 px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                                   placeholder="Например: Программирование">
                            <button onclick="openTypedSubject()" class="bg-white border border-slate-200 text-slate-700 px-4 py-3 rounded-2xl hover:border-sky-300 hover:text-sky-700 transition">
                                Новый журнал
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="newDateInput" class="block text-sm font-medium text-slate-700 mb-2">Добавить дату в таблицу</label>
                        <div class="flex flex-wrap gap-3">
                            <input id="newDateInput" type="date"
                                   class="flex-1 min-w-[220px] px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            <button onclick="addDateColumn()" class="bg-emerald-600 text-white px-4 py-3 rounded-2xl hover:bg-emerald-500 transition">
                                Добавить
                            </button>
                            <button onclick="addNextMonth()" class="bg-slate-900 text-white px-4 py-3 rounded-2xl hover:bg-slate-800 transition">
                                Добавить месяц
                            </button>
                        </div>
                    </div>

                    <div id="subjectChips" class="flex flex-wrap gap-2"></div>

                    <div id="saveHint" class="rounded-3xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                        Пустая ячейка означает, что оценки нет. Введите оценку и нажмите <span class="font-semibold text-slate-900">Enter</span> или просто уйдите из поля, чтобы сохранить запись на выбранную дату.
                    </div>
                </div>

                <div class="min-w-0">
                    <div class="flex flex-col gap-3 mb-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="text-sm text-slate-500">
                            Показываю неделю:
                            <span id="currentWeekLabel" class="font-semibold text-slate-900"></span>
                        </div>
                        <div class="flex flex-wrap gap-3 w-full lg:w-auto">
                            <button onclick="shiftWeek(-1)" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-2xl hover:border-sky-300 hover:text-sky-700 transition w-full sm:w-auto">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Прошлая неделя
                            </button>
                            <button onclick="shiftWeek(1)" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-2xl hover:border-sky-300 hover:text-sky-700 transition w-full sm:w-auto">
                                Следующая неделя
                                <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                    <div class="rounded-3xl border border-slate-200 overflow-hidden bg-white min-w-0">
                        <div class="overflow-auto max-w-full">
                            <div id="gradebookTable"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    const groupId = {{ $id }};
    const pageParams = new URLSearchParams(window.location.search);
    let selectedSubject = (pageParams.get('subject') || '').trim();
    let gradebook = null;
    let currentWeekStart = startOfWeek(new Date());

    async function apiFetch(url, options = {}) {
        return authApiFetch(url, options);
    }

    async function loadGradebook(subjectOverride = null) {
        const requestedSubject = typeof subjectOverride === 'string' ? subjectOverride.trim() : selectedSubject;
        const params = new URLSearchParams();
        if (requestedSubject) {
            params.set('subject_name', requestedSubject);
        }

        try {
            const response = await apiFetch(`/api/student-groups/${groupId}/gradebook?${params.toString()}`);
            if (!response.ok) {
                throw new Error('Не удалось загрузить журнал группы');
            }

            const payload = await response.json();
            gradebook = payload.data;
            selectedSubject = (gradebook.subject_name || requestedSubject || '').trim();

            renderPage();

            document.getElementById('loading').classList.add('hidden');
            document.getElementById('pageContent').classList.remove('hidden');
        } catch (error) {
            console.error(error);
            alert(error.message || 'Ошибка загрузки журнала');
        }
    }

    function renderPage() {
        if (!gradebook) {
            return;
        }

        ensureDefaultCalendarCoverage();

        document.getElementById('groupTitle').textContent = gradebook.group?.name || 'Группа';
        document.getElementById('groupDescription').textContent = gradebook.group?.description || 'В этом журнале можно вести оценки по разным предметам и датам для каждого студента группы.';
        document.getElementById('studentsCount').textContent = gradebook.students?.length || 0;
        document.getElementById('datesCount').textContent = getVisibleDates().length;
        document.getElementById('currentSubjectLabel').textContent = selectedSubject || 'Не выбран';
        document.getElementById('currentWeekLabel').textContent = formatWeekRange();
        document.getElementById('newDateInput').value = document.getElementById('newDateInput').value || todayDate();

        renderSubjectControls();
        renderGradebookTable();
        syncSubjectInUrl();
    }

    function renderSubjectControls() {
        const subjects = uniqueValues([
            ...(gradebook.available_subjects || []),
            selectedSubject
        ].filter(Boolean));

        const subjectSelect = document.getElementById('subjectSelect');
        if (!subjects.length) {
            subjectSelect.innerHTML = '<option value="">Сначала введите предмет ниже</option>';
        } else {
            subjectSelect.innerHTML = subjects.map((subject) => `
                <option value="${escapeAttribute(subject)}" ${subject === selectedSubject ? 'selected' : ''}>${escapeHtml(subject)}</option>
            `).join('');
        }

        const subjectChips = document.getElementById('subjectChips');
        if (!subjects.length) {
            subjectChips.innerHTML = '<span class="text-sm text-slate-400">Предметы еще не добавлены</span>';
        } else {
            subjectChips.innerHTML = subjects.map((subject) => `
                <button onclick="loadGradebook(decodeURIComponent('${encodeForInline(subject)}'))"
                        class="px-3 py-2 rounded-full text-sm transition ${subject === selectedSubject ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'}">
                    ${escapeHtml(subject)}
                </button>
            `).join('');
        }

        document.getElementById('newSubjectInput').value = selectedSubject || '';
    }

    function renderGradebookTable() {
        const container = document.getElementById('gradebookTable');
        const dates = getVisibleDates();
        const students = gradebook.students || [];

        if (!students.length) {
            container.innerHTML = `
                <div class="p-12 text-center text-slate-500">
                    В этой группе пока нет студентов.
                </div>
            `;
            return;
        }

        const headColumns = dates.map((date) => `
            <th data-date-column="true" title="${formatLongDate(date)}" class="sticky top-0 z-20 bg-slate-900 text-white min-w-[132px] px-4 py-4 border-l border-slate-700 text-center">
                <div class="font-semibold">${formatShortDate(date)}</div>
                <div class="text-xs text-slate-300 mt-1">${formatWeekday(date)}</div>
            </th>
        `).join('');

        const bodyRows = students.map((student) => `
            <tr class="even:bg-slate-50/60">
                <th class="sticky left-0 z-10 bg-white min-w-[280px] max-w-[280px] px-4 py-4 text-left border-r border-b border-slate-200 align-top">
                    <div class="font-semibold text-slate-900">${escapeHtml(student.full_name)}</div>
                </th>
                ${dates.map((date) => renderGradeCell(student, date)).join('')}
            </tr>
        `).join('');

        container.innerHTML = `
            <table class="min-w-full border-separate border-spacing-0">
                <thead>
                    <tr>
                        <th class="sticky top-0 left-0 z-30 bg-slate-900 text-white min-w-[280px] px-4 py-4 text-left border-r border-slate-700">
                            Студенты
                        </th>
                        ${headColumns || `
                            <th class="sticky top-0 z-20 bg-slate-900 text-white px-4 py-4 text-left">
                                Добавьте первую дату, чтобы начать ставить оценки
                            </th>
                        `}
                    </tr>
                </thead>
                <tbody>
                    ${bodyRows}
                </tbody>
            </table>
        `;
    }

    function renderGradeCell(student, date) {
        const entry = student.grades?.[date] || null;
        const value = entry?.grade_value || '';
        const linkedHint = entry?.blank_form_id
            ? '<div class="text-[10px] text-sky-600 mt-1">по проверенной работе</div>'
            : '<div class="text-[10px] text-slate-300 mt-1">ручная запись</div>';

        return `
            <td class="min-w-[132px] px-3 py-3 border-b border-l border-slate-200 align-top">
                <input type="text"
                       value="${escapeAttribute(value)}"
                       data-student-id="${student.id}"
                       data-date="${date}"
                       data-last-saved="${escapeAttribute(value)}"
                       onfocus="this.select()"
                       onkeydown="handleCellKeydown(event, this)"
                       onblur="saveGradeCell(this)"
                       class="w-full px-3 py-2 rounded-xl border text-center font-semibold ${value ? 'border-slate-300 bg-white text-slate-900' : 'border-dashed border-slate-300 bg-slate-50 text-slate-500'} focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                ${linkedHint}
            </td>
        `;
    }

    function getDates() {
        return sortDates([...(gradebook?.dates || [])]);
    }

    function getVisibleDates() {
        return buildWeekDates(currentWeekStart);
    }

    function ensureDefaultCalendarCoverage() {
        const today = new Date();
        gradebook.dates = sortDates([
            ...(gradebook.dates || []),
            ...buildMonthDates(today.getFullYear(), today.getMonth()),
            ...buildMonthDates(today.getFullYear(), today.getMonth() + 1),
        ]);
    }

    function getNextMonthToAppend() {
        const dates = getDates();
        if (!dates.length) {
            const today = new Date();
            return {
                year: today.getFullYear(),
                monthIndex: today.getMonth() + 2,
            };
        }

        const lastDate = parseDateFromIso(dates[dates.length - 1]);
        return {
            year: lastDate.getFullYear(),
            monthIndex: lastDate.getMonth() + 1,
        };
    }

    function buildMonthDates(year, monthIndex) {
        const monthStart = new Date(year, monthIndex, 1);
        const dates = [];
        const cursor = new Date(monthStart.getFullYear(), monthStart.getMonth(), 1);

        while (cursor.getMonth() === monthStart.getMonth()) {
            dates.push(formatDateToIso(cursor));
            cursor.setDate(cursor.getDate() + 1);
        }

        return dates;
    }

    function openSelectedSubject() {
        const value = (document.getElementById('subjectSelect').value || '').trim();
        if (!value) {
            alert('Выберите предмет или введите новый');
            return;
        }

        loadGradebook(value);
    }

    function openTypedSubject() {
        const value = (document.getElementById('newSubjectInput').value || '').trim();
        if (!value) {
            alert('Введите название предмета');
            return;
        }

        loadGradebook(value);
    }

    function addDateColumn() {
        if (!gradebook) {
            return;
        }

        const dateInput = document.getElementById('newDateInput');
        const dateValue = normalizeDateValue(dateInput.value);
        if (!dateValue) {
            alert('Выберите дату');
            return;
        }

        gradebook.dates = sortDates([...(gradebook.dates || []), dateValue]);
        currentWeekStart = startOfWeek(parseDateFromIso(dateValue));
        renderPage();

        const firstInput = document.querySelector(`input[data-date="${dateValue}"]`);
        firstInput?.focus();
    }

    function addNextMonth() {
        if (!gradebook) {
            return;
        }

        const nextMonth = getNextMonthToAppend();
        const firstDate = formatDateToIso(new Date(nextMonth.year, nextMonth.monthIndex, 1));
        gradebook.dates = sortDates([
            ...(gradebook.dates || []),
            ...buildMonthDates(nextMonth.year, nextMonth.monthIndex)
        ]);
        currentWeekStart = startOfWeek(parseDateFromIso(firstDate));
        renderPage();
    }

    function shiftWeek(direction) {
        currentWeekStart = addDays(currentWeekStart, direction * 7);
        renderPage();
    }

    async function saveGradeCell(input) {
        const studentId = Number(input.dataset.studentId);
        const gradeDate = input.dataset.date;
        const nextValue = input.value.trim();
        const lastSaved = input.dataset.lastSaved || '';
        const subjectName = selectedSubject.trim();

        if (nextValue === lastSaved) {
            return;
        }

        if (!subjectName) {
            alert('Сначала выберите или создайте предмет журнала');
            input.value = lastSaved;
            return;
        }

        input.disabled = true;
        input.classList.add('opacity-70');

        try {
            const response = await apiFetch(`/api/student-groups/${groupId}/gradebook-entry`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    group_student_id: studentId,
                    subject_name: subjectName,
                    grade_date: gradeDate,
                    grade_value: nextValue || null
                })
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || 'Не удалось сохранить оценку');
            }

            const payload = await response.json();
            applySavedEntry(studentId, gradeDate, payload.data);
            input.dataset.lastSaved = nextValue;
            renderPage();
        } catch (error) {
            console.error(error);
            alert(error.message || 'Ошибка сохранения оценки');
            input.value = lastSaved;
        } finally {
            input.disabled = false;
            input.classList.remove('opacity-70');
        }
    }

    function applySavedEntry(studentId, gradeDate, entry) {
        if (!gradebook) {
            return;
        }

        const student = (gradebook.students || []).find((item) => Number(item.id) === Number(studentId));
        if (!student) {
            return;
        }

        student.grades = student.grades || {};

        if (!entry) {
            delete student.grades[gradeDate];
            return;
        }

        student.grades[gradeDate] = {
            id: entry.id,
            grade_value: entry.grade_value,
            grade_date: normalizeDateValue(entry.grade_date),
            blank_form_id: entry.blank_form_id,
            subject_name: entry.subject_name
        };

        gradebook.available_subjects = uniqueValues([
            ...(gradebook.available_subjects || []),
            entry.subject_name
        ]);
    }

    function handleCellKeydown(event, input) {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        input.blur();
    }

    function syncSubjectInUrl() {
        const url = new URL(window.location.href);

        if (selectedSubject) {
            url.searchParams.set('subject', selectedSubject);
        } else {
            url.searchParams.delete('subject');
        }

        window.history.replaceState({}, '', url);
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

    function parseDateFromIso(value) {
        const normalized = normalizeDateValue(value);
        const [year, month, day] = normalized.split('-').map((item) => Number(item));
        return new Date(year, month - 1, day);
    }

    function addDays(date, days) {
        const next = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        next.setDate(next.getDate() + days);
        return next;
    }

    function startOfWeek(date) {
        const normalized = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const dayIndex = (normalized.getDay() + 6) % 7;
        normalized.setDate(normalized.getDate() - dayIndex);
        normalized.setHours(0, 0, 0, 0);
        return normalized;
    }

    function buildWeekDates(weekStart) {
        return Array.from({ length: 7 }, (_, index) => formatDateToIso(addDays(weekStart, index)));
    }

    function formatDateToIso(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function todayDate() {
        return new Date().toISOString().slice(0, 10);
    }

    function sortDates(values) {
        return uniqueValues((values || []).map(normalizeDateValue).filter(Boolean)).sort((a, b) => a.localeCompare(b));
    }

    function uniqueValues(values) {
        return [...new Set((values || []).filter(Boolean))];
    }

    function formatShortDate(value) {
        const normalized = normalizeDateValue(value);
        if (!normalized) {
            return 'Без даты';
        }

        return new Date(`${normalized}T00:00:00`).toLocaleDateString('ru-RU', {
            day: '2-digit',
            month: '2-digit'
        });
    }

    function formatWeekday(value) {
        const normalized = normalizeDateValue(value);
        if (!normalized) {
            return '';
        }

        return new Date(`${normalized}T00:00:00`).toLocaleDateString('ru-RU', {
            weekday: 'short'
        });
    }

    function formatWeekRange() {
        const dates = getVisibleDates();
        if (!dates.length) {
            return 'Без дат';
        }

        return `${formatShortDate(dates[0])} - ${formatShortDate(dates[dates.length - 1])}`;
    }

    function formatLongDate(value) {
        const normalized = normalizeDateValue(value);
        if (!normalized) {
            return 'Без даты';
        }

        return new Date(`${normalized}T00:00:00`).toLocaleDateString('ru-RU', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
            weekday: 'long'
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function escapeAttribute(text) {
        return escapeHtml(text).replace(/"/g, '&quot;');
    }

    function encodeForInline(text) {
        return encodeURIComponent(String(text || ''));
    }

    document.addEventListener('DOMContentLoaded', async () => {
        if (!await ensureAuthenticatedPage()) {
            return;
        }

        document.getElementById('newDateInput').value = todayDate();
        loadGradebook(selectedSubject);
    });
</script>
</body>
</html>
