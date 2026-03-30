<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест | BlanksProject</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">

@include('layouts.nav')

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div id="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sky-500"></div>
        <p class="text-slate-600 mt-4">Загружаю тест...</p>
    </div>

    <div id="pageContent" class="hidden space-y-6">
        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-wrap justify-between items-start gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-sky-700 font-semibold">Карточка теста</p>
                    <h1 id="testTitle" class="text-3xl font-bold mt-2"></h1>
                    <div id="testSubject" class="text-slate-500 mt-2 font-medium"></div>
                    <p id="testDescription" class="text-slate-600 mt-3 max-w-3xl"></p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button onclick="window.location.href=`/tests/${testId}/edit`" class="bg-sky-600 text-white px-4 py-3 rounded-2xl hover:bg-sky-500 transition flex items-center gap-2">
                        <i class="fas fa-pen"></i>
                        Редактировать
                    </button>
                    <button onclick="openPrintMode('all')" class="bg-white border border-slate-200 text-slate-700 px-4 py-3 rounded-2xl hover:border-sky-300 hover:text-sky-700 transition flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        Демо-комплект
                    </button>
                    <button onclick="openPrintMode('blank')" class="bg-white border border-slate-200 text-slate-700 px-4 py-3 rounded-2xl hover:border-sky-300 hover:text-sky-700 transition flex items-center gap-2">
                        <i class="fas fa-table"></i>
                        Только бланк
                    </button>
                    <button onclick="openPrintMode('questions')" class="bg-white border border-slate-200 text-slate-700 px-4 py-3 rounded-2xl hover:border-sky-300 hover:text-sky-700 transition flex items-center gap-2">
                        <i class="fas fa-list"></i>
                        Только задания
                    </button>
                    <button onclick="window.location.href='/tests'" class="bg-slate-900 text-white px-4 py-3 rounded-2xl hover:bg-slate-800 transition">
                        Назад
                    </button>
                </div>
            </div>

            <div class="grid md:grid-cols-4 gap-4 mt-6">
                <div class="bg-slate-50 rounded-2xl p-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Вопросы</div>
                    <div id="questionCount" class="text-2xl font-bold mt-2">0</div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Макс. балл</div>
                    <div id="maxPoints" class="text-2xl font-bold mt-2">0</div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Время</div>
                    <div id="timeLimit" class="text-2xl font-bold mt-2">Без лимита</div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400">Статус</div>
                    <div id="testStatus" class="text-2xl font-bold mt-2">Черновик</div>
                </div>
            </div>
        </section>

        <section class="grid xl:grid-cols-[1.2fr_0.8fr] gap-6">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <div class="flex justify-between items-center gap-3 mb-4">
                    <div>
                        <h2 class="text-xl font-semibold">Вопросы</h2>
                        <p class="text-slate-500 mt-1">Вопросы и правильные ответы теста.</p>
                    </div>
                </div>

                <div id="questionsList" class="space-y-4"></div>
            </div>

            <div class="space-y-6">
                <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-semibold">Шкала оценивания</h2>
                    <p class="text-slate-500 mt-1">Эти пороги применяются при автоматической проверке сканов.</p>
                    <div id="gradeCriteriaList" class="mt-4 space-y-3"></div>
                </section>

                <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-semibold">Сканирование</h2>
                    <div id="scanSupportNote" class="mt-3 text-sm rounded-2xl border border-amber-200 bg-amber-50 text-amber-800 p-4 hidden"></div>
                    <div class="mt-4 text-sm text-slate-600">
                        Поддерживаются <span class="font-semibold">JPG / PNG / WEBP / PDF</span>. Если загружен PDF, браузер автоматически преобразует все его листы в изображения перед отправкой.
                    </div>
                </section>
            </div>
        </section>

        <section id="workflow" class="grid xl:grid-cols-[0.95fr_1.05fr] gap-6">
            <div class="space-y-6">
                <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                    <div class="flex justify-between items-center gap-3">
                        <div>
                            <h2 class="text-xl font-semibold">Выпуск бланков</h2>
                            <p class="text-slate-500 mt-1">Сгенерируйте персональные бланки для всей группы или только для отмеченных студентов.</p>
                        </div>
                        <button onclick="window.location.href='/groups'" class="text-sky-600 hover:text-sky-800 text-sm font-medium">
                            Открыть группы
                        </button>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div>
                            <label for="groupSelect" class="block text-sm font-medium text-slate-700 mb-2">Группа</label>
                            <select id="groupSelect" onchange="handleGroupChange()" class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></select>
                        </div>

                        <div>
                            <div class="block text-sm font-medium text-slate-700 mb-2">Кому выпускать бланки</div>
                            <div class="grid sm:grid-cols-2 gap-3">
                                <button id="generateModeAllButton" type="button" onclick="setBlankGenerationMode('all')" class="rounded-2xl border border-slate-300 px-4 py-3 text-left transition hover:border-sky-300 hover:bg-white">
                                    <div class="font-semibold">Вся группа</div>
                                    <div class="text-sm mt-1 opacity-80">Бланки будут созданы для каждого студента выбранной группы.</div>
                                </button>
                                <button id="generateModeSelectedButton" type="button" onclick="setBlankGenerationMode('selected')" class="rounded-2xl border border-slate-300 px-4 py-3 text-left transition hover:border-sky-300 hover:bg-white">
                                    <div class="font-semibold">Только выбранные</div>
                                    <div class="text-sm mt-1 opacity-80">Ниже можно отметить только тех студентов, кому нужны бланки сейчас.</div>
                                </button>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap justify-between items-start gap-3">
                                <div>
                                    <div class="text-sm uppercase tracking-[0.25em] text-slate-400">Состав группы</div>
                                    <div id="studentSelectionSummary" class="text-sm text-slate-600 mt-2">Сначала выберите группу.</div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button id="selectAllStudentsButton" type="button" onclick="selectAllGroupStudents()" class="bg-white border border-slate-200 text-slate-700 px-3 py-2 rounded-xl hover:border-sky-300 hover:text-sky-700 transition text-sm">
                                        Отметить всех
                                    </button>
                                    <button id="clearStudentsButton" type="button" onclick="clearSelectedGroupStudents()" class="bg-white border border-slate-200 text-slate-700 px-3 py-2 rounded-xl hover:border-sky-300 hover:text-sky-700 transition text-sm">
                                        Снять выбор
                                    </button>
                                </div>
                            </div>
                            <div id="groupStudentsList" class="mt-4 grid sm:grid-cols-2 gap-3"></div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button onclick="generateBlankForms()" class="bg-emerald-600 text-white px-5 py-3 rounded-2xl hover:bg-emerald-500 transition font-medium">
                                Сгенерировать бланки
                            </button>
                            <div id="printGeneratedActions" class="hidden flex flex-wrap gap-3">
                                <button onclick="printGeneratedPack('all')" class="bg-slate-900 text-white px-5 py-3 rounded-2xl hover:bg-slate-800 transition font-medium">
                                    Пачка: комплект
                                </button>
                                <button onclick="printGeneratedPack('blank')" class="bg-white border border-slate-200 text-slate-700 px-5 py-3 rounded-2xl hover:border-sky-300 hover:text-sky-700 transition font-medium">
                                    Пачка: только бланки
                                </button>
                                <button onclick="printGeneratedPack('questions')" class="bg-white border border-slate-200 text-slate-700 px-5 py-3 rounded-2xl hover:border-sky-300 hover:text-sky-700 transition font-medium">
                                    Пачка: только задания
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-xl font-semibold">Загрузка сканов</h2>
                    <p class="text-slate-500 mt-1">Загрузите отсканированные листы бланков, и система автоматически поставит баллы и оценку.</p>

                    <div class="mt-5 space-y-4">
                        <input id="scanFiles" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                               class="w-full px-4 py-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50">
                        <button id="scanButton" onclick="uploadScans()" class="bg-sky-600 text-white px-5 py-3 rounded-2xl hover:bg-sky-500 transition font-medium">
                            Обработать сканы
                        </button>
                    </div>

                    <div id="scanResults" class="mt-5 space-y-3"></div>
                </section>
            </div>

            <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <div>
                        <h2 class="text-xl font-semibold">Персональные бланки</h2>
                        <p class="text-slate-500 mt-1">Список уже выпущенных бланков и результатов проверки.</p>
                    </div>
                    <button onclick="loadBlankForms()" class="text-sky-600 hover:text-sky-800 text-sm font-medium">
                        Обновить список
                    </button>
                </div>

                <div id="blankFormsList" class="space-y-3"></div>
            </section>
        </section>
    </div>
</div>

<script>
    const testId = {{ $id }};
    const SCAN_ROWS_PER_PAGE = 24;
    let currentTest = null;
    let groups = [];
    let blankForms = [];
    let lastGeneratedBlankIds = [];
    let blankGenerationMode = 'all';
    let selectedGroupStudentIds = [];
    let pdfJsLoadingPromise = null;

    async function apiFetch(url, options = {}) {
        return authApiFetch(url, options);
    }

    async function loadPage() {
        try {
            const [testResponse, groupsResponse] = await Promise.all([
                apiFetch(`/api/tests/${testId}`),
                apiFetch('/api/student-groups')
            ]);

            if (!testResponse.ok) {
                throw new Error('Не удалось загрузить тест');
            }

            currentTest = (await testResponse.json()).data;
            groups = groupsResponse.ok ? ((await groupsResponse.json()).data || []) : [];

            renderTest();
            renderGroups();
            await loadBlankForms();

            document.getElementById('loading').classList.add('hidden');
            document.getElementById('pageContent').classList.remove('hidden');

            if (window.location.hash === '#workflow') {
                document.getElementById('workflow').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (error) {
            console.error(error);
            alert(error.message || 'Ошибка загрузки страницы');
        }
    }

    function renderTest() {
        document.getElementById('testTitle').textContent = currentTest.title || 'Без названия';
        document.getElementById('testSubject').textContent = currentTest.subject_name
            ? `Предмет: ${currentTest.subject_name}`
            : 'Предмет не указан';
        document.getElementById('testDescription').textContent = currentTest.description || 'Описание не указано';
        document.getElementById('questionCount').textContent = currentTest.questions?.length || 0;
        document.getElementById('maxPoints').textContent = (currentTest.questions || []).reduce((sum, question) => sum + (question.points || 0), 0);
        document.getElementById('timeLimit').textContent = currentTest.time_limit ? `${currentTest.time_limit} мин` : 'Без лимита';
        document.getElementById('testStatus').textContent = currentTest.is_active ? 'Активен' : 'Черновик';

        const questionsList = document.getElementById('questionsList');
        questionsList.innerHTML = (currentTest.questions || []).map((question, index) => `
            <article class="border border-slate-200 rounded-2xl p-4 ${question.type === 'multiple' ? 'bg-violet-50' : 'bg-slate-50'}">
                <div class="flex justify-between items-start gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">${index + 1}. ${escapeHtml(question.question_text)}</h3>
                        <div class="text-sm text-slate-500 mt-2">
                            ${question.type === 'single' ? 'Один правильный ответ' : 'Несколько правильных ответов'}
                        </div>
                    </div>
                    <span class="bg-white border border-slate-200 rounded-full px-3 py-1 text-sm font-semibold text-slate-700">
                        ${question.points || 1} балл.
                    </span>
                </div>

                <div class="mt-4 grid gap-2">
                    ${(question.answers || []).map((answer, answerIndex) => `
                        <div class="flex items-start gap-3 rounded-xl px-3 py-2 ${answer.is_correct ? 'bg-emerald-100 text-emerald-900' : 'bg-white'}">
                            <span class="font-semibold">${String.fromCharCode(65 + answerIndex)}.</span>
                            <span>${escapeHtml(answer.answer_text)}</span>
                            ${answer.is_correct ? '<i class="fas fa-check mt-1 text-emerald-700"></i>' : ''}
                        </div>
                    `).join('')}
                </div>
            </article>
        `).join('');

        const criteria = [...(currentTest.grade_criteria || [])].sort((a, b) => b.min_points - a.min_points);
        document.getElementById('gradeCriteriaList').innerHTML = criteria.map((criterion) => `
            <div class="flex justify-between items-center bg-slate-50 rounded-2xl px-4 py-3">
                <span class="font-medium text-slate-800">${escapeHtml(criterion.label)}</span>
                <span class="text-slate-600">от <span class="font-semibold text-slate-900">${criterion.min_points}</span> балл.</span>
            </div>
        `).join('');

        const questionCount = currentTest.questions?.length || 0;
        const hasTooManyAnswers = (currentTest.questions || []).some((question) => (question.answers || []).length > 5);
        const scanSupportNote = document.getElementById('scanSupportNote');
        const answerSheetPageCount = Math.max(1, Math.ceil(questionCount / SCAN_ROWS_PER_PAGE));
        const scanButton = document.getElementById('scanButton');

        scanButton.disabled = false;
        scanButton.classList.remove('opacity-50', 'cursor-not-allowed');

        if (hasTooManyAnswers) {
            scanSupportNote.classList.remove('hidden');
            scanSupportNote.textContent = 'В одном или нескольких вопросах больше 5 вариантов ответа. Текущий формат автосканирования поддерживает максимум 5.';
            scanButton.disabled = true;
            scanButton.classList.add('opacity-50', 'cursor-not-allowed');
        } else if (answerSheetPageCount > 1) {
            scanSupportNote.classList.remove('hidden');
            scanSupportNote.textContent = `Для этого теста понадобится ${answerSheetPageCount} листа(ов) ответов на каждого ученика. При проверке загружайте все листы ученика одной пачкой.`;
        } else {
            scanSupportNote.classList.add('hidden');
        }
    }

    function renderGroups() {
        const select = document.getElementById('groupSelect');

        if (!groups.length) {
            select.innerHTML = '<option value="">Сначала создайте учебную группу</option>';
            renderGroupStudents();
            return;
        }

        select.innerHTML = `
            <option value="">Выберите группу</option>
            ${groups.map((group) => `<option value="${group.id}">${escapeHtml(group.name)} (${group.students?.length || 0})</option>`).join('')}
        `;

        renderGroupStudents();
    }

    function getSelectedGroup() {
        const groupId = Number(document.getElementById('groupSelect').value);
        if (!Number.isInteger(groupId) || groupId <= 0) {
            return null;
        }

        return groups.find((group) => Number(group.id) === groupId) || null;
    }

    function getSelectedGroupStudents() {
        return getSelectedGroup()?.students || [];
    }

    function getAllSelectedGroupStudentIds() {
        return getSelectedGroupStudents()
            .map((student) => Number(student.id))
            .filter((value) => Number.isInteger(value) && value > 0);
    }

    function normalizeBufferedSelectedGroupStudentIds() {
        const allowedIds = new Set(getAllSelectedGroupStudentIds());

        return [...new Set(selectedGroupStudentIds
            .map((value) => Number(value))
            .filter((value) => allowedIds.has(value)))];
    }

    function getRenderableSelectedGroupStudentIds() {
        return blankGenerationMode === 'all'
            ? getAllSelectedGroupStudentIds()
            : normalizeBufferedSelectedGroupStudentIds();
    }

    function handleGroupChange() {
        selectedGroupStudentIds = [];
        blankGenerationMode = 'all';
        syncBlankGenerationModeButtons();
        renderGroupStudents();
    }

    function setBlankGenerationMode(mode) {
        blankGenerationMode = mode === 'selected' ? 'selected' : 'all';
        if (blankGenerationMode === 'all') {
            selectedGroupStudentIds = [];
        } else {
            selectedGroupStudentIds = normalizeBufferedSelectedGroupStudentIds();
        }
        syncBlankGenerationModeButtons();
        renderGroupStudents();
    }

    function syncBlankGenerationModeButtons() {
        const allButton = document.getElementById('generateModeAllButton');
        const selectedButton = document.getElementById('generateModeSelectedButton');
        const isAllMode = blankGenerationMode === 'all';

        if (!allButton || !selectedButton) {
            return;
        }

        allButton.className = isAllMode
            ? 'rounded-2xl border border-slate-900 bg-slate-900 px-4 py-3 text-left transition text-white'
            : 'rounded-2xl border border-slate-300 px-4 py-3 text-left transition hover:border-sky-300 hover:bg-white';
        selectedButton.className = !isAllMode
            ? 'rounded-2xl border border-slate-900 bg-slate-900 px-4 py-3 text-left transition text-white'
            : 'rounded-2xl border border-slate-300 px-4 py-3 text-left transition hover:border-sky-300 hover:bg-white';
    }

    function renderGroupStudents() {
        const list = document.getElementById('groupStudentsList');
        const summary = document.getElementById('studentSelectionSummary');
        const selectAllButton = document.getElementById('selectAllStudentsButton');
        const clearButton = document.getElementById('clearStudentsButton');
        const group = getSelectedGroup();
        const students = group?.students || [];
        const selectedIds = new Set(getRenderableSelectedGroupStudentIds());

        if (!list || !summary || !selectAllButton || !clearButton) {
            return;
        }

        if (!group) {
            summary.textContent = 'Сначала выберите группу.';
            list.innerHTML = `
                <div class="sm:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-slate-500">
                    После выбора группы здесь появится список ее студентов.
                </div>
            `;
            setStudentSelectionButtonsState(true);
            return;
        }

        if (!students.length) {
            summary.textContent = 'В этой группе пока нет студентов.';
            list.innerHTML = `
                <div class="sm:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-slate-500">
                    Добавьте студентов в группу, и после этого можно будет выпускать для них бланки.
                </div>
            `;
            setStudentSelectionButtonsState(true);
            return;
        }

        setStudentSelectionButtonsState(false);

        const selectedCount = students.filter((student) => selectedIds.has(Number(student.id))).length;
        summary.textContent = blankGenerationMode === 'selected'
            ? `Выбрано ${selectedCount} из ${students.length}. Будут выпущены бланки только для отмеченных студентов.`
            : `Сейчас будет выпущена вся группа: ${students.length} студент(ов). При необходимости переключитесь на выборочный режим.`;

        list.innerHTML = students.map((student) => {
            const studentId = Number(student.id);
            const isChecked = selectedIds.has(studentId);

            return `
                <label class="flex items-start gap-3 rounded-2xl border px-4 py-3 cursor-pointer transition ${isChecked ? 'border-sky-300 bg-white shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300'}">
                    <input type="checkbox"
                           ${isChecked ? 'checked' : ''}
                           onchange="toggleGroupStudent(${studentId}, this.checked)"
                           class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <div class="min-w-0">
                        <div class="font-medium text-slate-900">${escapeHtml(student.full_name || 'Без имени')}</div>
                    </div>
                </label>
            `;
        }).join('');
    }

    function setStudentSelectionButtonsState(disabled) {
        const buttons = [
            document.getElementById('selectAllStudentsButton'),
            document.getElementById('clearStudentsButton')
        ].filter(Boolean);

        buttons.forEach((button) => {
            button.disabled = disabled;
            button.classList.toggle('opacity-50', disabled);
            button.classList.toggle('cursor-not-allowed', disabled);
        });
    }

    function toggleGroupStudent(studentId, checked) {
        const allIds = getAllSelectedGroupStudentIds();
        const nextIds = new Set(getRenderableSelectedGroupStudentIds());

        if (checked) {
            nextIds.add(Number(studentId));
        } else {
            nextIds.delete(Number(studentId));
        }

        const nextSelectedIds = Array.from(nextIds).filter((value) => allIds.includes(value));
        if (allIds.length && nextSelectedIds.length === allIds.length) {
            blankGenerationMode = 'all';
            selectedGroupStudentIds = [];
        } else {
            blankGenerationMode = 'selected';
            selectedGroupStudentIds = nextSelectedIds;
        }

        syncBlankGenerationModeButtons();
        renderGroupStudents();
    }

    function selectAllGroupStudents() {
        blankGenerationMode = 'all';
        syncBlankGenerationModeButtons();
        selectedGroupStudentIds = [];
        renderGroupStudents();
    }

    function clearSelectedGroupStudents() {
        blankGenerationMode = 'selected';
        syncBlankGenerationModeButtons();
        selectedGroupStudentIds = [];
        renderGroupStudents();
    }

    async function loadBlankForms() {
        const response = await apiFetch(`/api/blank-forms?test_id=${testId}&per_page=100`);
        if (!response.ok) {
            throw new Error('Не удалось загрузить бланки');
        }

        const data = await response.json();
        blankForms = data.data?.data || data.data || [];
        renderBlankForms();
    }

    function renderBlankForms() {
        const list = document.getElementById('blankFormsList');

        if (!blankForms.length) {
            list.innerHTML = `
                <div class="text-center py-10 rounded-2xl border border-dashed border-slate-300 text-slate-500">
                    Для этого теста пока не выпущено ни одного персонального бланка.
                </div>
            `;
            return;
        }

        list.innerHTML = blankForms.map((blankForm) => {
            const statusMap = {
                generated: ['Сгенерирован', 'bg-slate-100 text-slate-700'],
                submitted: ['Загружен', 'bg-amber-100 text-amber-800'],
                checked: ['Проверен', 'bg-emerald-100 text-emerald-800']
            };

            const [statusLabel, statusClass] = statusMap[blankForm.status] || ['Неизвестно', 'bg-slate-100 text-slate-600'];
            const studentName = [blankForm.last_name, blankForm.first_name, blankForm.patronymic].filter(Boolean).join(' ') || 'Без имени';
            const assignedGrade = blankForm.assigned_grade_value
                ? `${escapeHtml(blankForm.assigned_grade_value)} • ${formatDate(blankForm.assigned_grade_date)}`
                : '';

            return `
                <article class="border border-slate-200 rounded-2xl p-4">
                    <div class="flex flex-wrap justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="font-semibold text-slate-900">${escapeHtml(studentName)}</h3>
                                <span class="px-3 py-1 rounded-full text-xs ${statusClass}">${statusLabel}</span>
                            </div>
                            <div class="text-sm text-slate-500 mt-2">${escapeHtml(blankForm.group_name || 'Группа не указана')}</div>
                            <div class="text-xs text-slate-400 mt-2">${escapeHtml(blankForm.form_number || '')}</div>
                        </div>

                        <div class="text-right">
                            <div class="text-sm text-slate-500">Результат</div>
                            <div class="font-semibold text-slate-900">${blankForm.total_score ?? '—'} ${blankForm.grade_label ? `• ${escapeHtml(blankForm.grade_label)}` : ''}</div>
                            ${assignedGrade ? `<div class="text-xs text-slate-500 mt-2">Поставленная оценка: ${assignedGrade}</div>` : ''}
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-4">
                        <button onclick="printBlankForm(${blankForm.id}, 'all')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl hover:border-sky-300 hover:text-sky-700 transition text-sm">
                            Комплект
                        </button>
                        <button onclick="printBlankForm(${blankForm.id}, 'blank')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl hover:border-sky-300 hover:text-sky-700 transition text-sm">
                            Бланк
                        </button>
                        <button onclick="printBlankForm(${blankForm.id}, 'questions')" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl hover:border-sky-300 hover:text-sky-700 transition text-sm">
                            Задания
                        </button>
                        ${blankForm.status === 'checked' ? `
                            <button onclick="openResultsPage([${blankForm.id}])" class="bg-sky-600 text-white px-4 py-2 rounded-xl hover:bg-sky-500 transition text-sm">
                                Разбор
                            </button>
                        ` : ''}
                        ${['generated', 'checked'].includes(blankForm.status) ? `
                            <button onclick="deleteBlankForm(${blankForm.id}, '${blankForm.status}')" class="bg-rose-600 text-white px-4 py-2 rounded-xl hover:bg-rose-500 transition text-sm">
                                Удалить
                            </button>
                        ` : ''}
                    </div>
                </article>
            `;
        }).join('');
    }

    async function generateBlankForms() {
        const groupId = document.getElementById('groupSelect').value;
        if (!groupId) {
            alert('Выберите группу');
            return;
        }

        const students = getSelectedGroupStudents();
        if (!students.length) {
            alert('В выбранной группе пока нет студентов');
            return;
        }

        const selectedIds = normalizeBufferedSelectedGroupStudentIds();

        if (blankGenerationMode === 'selected' && !selectedIds.length) {
            alert('Отметьте хотя бы одного студента для выборочной генерации');
            return;
        }

        try {
            const payload = {
                student_group_id: parseInt(groupId, 10)
            };

            if (blankGenerationMode === 'selected') {
                payload.group_student_ids = selectedIds;
            }

            const response = await apiFetch(`/api/tests/${testId}/generate-blank-forms`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Не удалось сгенерировать бланки');
            }

            const data = await response.json();
            lastGeneratedBlankIds = (data.data || []).map((item) => item.id);
            document.getElementById('printGeneratedActions').classList.toggle('hidden', !lastGeneratedBlankIds.length);
            await loadBlankForms();
            alert(blankGenerationMode === 'selected'
                ? `Сгенерировано бланков для выбранных студентов: ${lastGeneratedBlankIds.length}`
                : `Сгенерировано бланков: ${lastGeneratedBlankIds.length}`);
        } catch (error) {
            alert(error.message || 'Ошибка генерации');
        }
    }

    function openPrintMode(mode = 'all') {
        window.location.href = buildPrintUrl([], mode);
    }

    function printGeneratedPack(mode = 'all') {
        if (!lastGeneratedBlankIds.length) {
            return;
        }

        window.location.href = buildPrintUrl(lastGeneratedBlankIds, mode);
    }

    function printBlankForm(blankFormId, mode = 'all') {
        window.location.href = buildPrintUrl([blankFormId], mode);
    }

    function buildPrintUrl(blankFormIds = [], mode = 'all') {
        const params = new URLSearchParams();
        if (blankFormIds.length) {
            params.set('blank_form_ids', blankFormIds.join(','));
        }
        if (mode && mode !== 'all') {
            params.set('print_mode', mode);
        }

        const query = params.toString();
        return query ? `/tests/${testId}/print?${query}` : `/tests/${testId}/print`;
    }

    async function deleteBlankForm(blankFormId, status) {
        const actionLabel = status === 'checked' ? 'проверенную работу' : 'сгенерированный бланк';
        if (!confirm(`Удалить ${actionLabel}? Это действие нельзя отменить.`)) {
            return;
        }

        try {
            const response = await apiFetch(`/api/blank-forms/${blankFormId}`, {
                method: 'DELETE'
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || 'Не удалось удалить бланк');
            }

            lastGeneratedBlankIds = lastGeneratedBlankIds.filter((id) => id !== blankFormId);
            document.getElementById('printGeneratedActions').classList.toggle('hidden', !lastGeneratedBlankIds.length);
            await loadBlankForms();
        } catch (error) {
            alert(error.message || 'Ошибка удаления');
        }
    }

    function openResultsPage(blankFormIds) {
        const ids = [...new Set((blankFormIds || []).map((value) => Number(value)).filter((value) => Number.isInteger(value) && value > 0))];

        if (!ids.length) {
            return;
        }

        const params = new URLSearchParams({
            ids: ids.join(','),
            test_id: String(testId)
        });

        window.location.href = `/blank-forms/results?${params.toString()}`;
    }

    async function uploadScans() {
        const input = document.getElementById('scanFiles');
        const scanButton = document.getElementById('scanButton');
        if (!input.files.length) {
            alert('Выберите хотя бы один файл');
            return;
        }

        const originalFiles = Array.from(input.files);
        const formData = new FormData();

        try {
            scanButton.disabled = true;
            scanButton.classList.add('opacity-70', 'cursor-wait');
            scanButton.textContent = 'Обрабатываю...';
            const preparedFiles = await prepareScanFilesForUpload(originalFiles, scanButton);
            preparedFiles.forEach((file) => formData.append('scans[]', file));

            const response = await authApiFetch(`/api/tests/${testId}/scan-blank-forms`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Не удалось обработать сканы');
            }

            const data = await response.json();
            const results = data.data || [];
            const processedIds = [...new Set(results
                .filter((result) => Number.isInteger(Number(result.blank_form_id)) && result.status === 'checked')
                .map((result) => Number(result.blank_form_id))
                .filter((value) => value > 0))];
            input.value = '';

            if (processedIds.length && processedIds.length === results.length) {
                openResultsPage(processedIds);
                return;
            }

            renderScanResults(results);
            await loadBlankForms();
        } catch (error) {
            alert(error.message || 'Ошибка загрузки сканов');
        } finally {
            scanButton.disabled = false;
            scanButton.classList.remove('opacity-70', 'cursor-wait');
            scanButton.textContent = 'Обработать сканы';
        }
    }

    async function prepareScanFilesForUpload(files, scanButton) {
        const preparedFiles = [];

        for (let index = 0; index < files.length; index += 1) {
            const file = files[index];
            if (isPdfFile(file)) {
                scanButton.textContent = `Готовлю PDF ${index + 1}/${files.length}...`;
                preparedFiles.push(...await convertPdfToImageFiles(file));
                continue;
            }

            preparedFiles.push(file);
        }

        return preparedFiles;
    }

    function isPdfFile(file) {
        return file.type === 'application/pdf' || /\.pdf$/i.test(file.name || '');
    }

    async function convertPdfToImageFiles(file) {
        const pdfjsLib = await ensurePdfJsLoaded();
        const buffer = await file.arrayBuffer();
        const loadingTask = pdfjsLib.getDocument({ data: buffer });
        const pdf = await loadingTask.promise;
        const files = [];

        try {
            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
                const page = await pdf.getPage(pageNumber);
                const viewport = page.getViewport({ scale: 2.2 });
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d', { alpha: false });

                if (!context) {
                    throw new Error('Не удалось подготовить холст для конвертации PDF.');
                }

                canvas.width = Math.ceil(viewport.width);
                canvas.height = Math.ceil(viewport.height);
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);

                await page.render({
                    canvasContext: context,
                    viewport,
                    background: 'rgb(255,255,255)',
                }).promise;

                const blob = await new Promise((resolve, reject) => {
                    canvas.toBlob((result) => {
                        if (result) {
                            resolve(result);
                            return;
                        }

                        reject(new Error(`Не удалось сохранить изображение листа ${pageNumber} из PDF.`));
                    }, 'image/jpeg', 0.92);
                });

                const targetName = (file.name || 'scan.pdf').replace(/\.pdf$/i, '') + `-page${pageNumber}.jpg`;
                files.push(new File([blob], targetName, {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                }));
            }

            return files;
        } finally {
            await pdf.destroy();
        }
    }

    async function ensurePdfJsLoaded() {
        if (window.__pdfjsLib) {
            return window.__pdfjsLib;
        }

        if (!pdfJsLoadingPromise) {
            pdfJsLoadingPromise = import('https://cdn.jsdelivr.net/npm/pdfjs-dist@5.4.624/build/pdf.min.mjs')
                .then((module) => {
                    const pdfjsLib = module.default || module;
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@5.4.624/build/pdf.worker.min.mjs';
                    window.__pdfjsLib = pdfjsLib;

                    return pdfjsLib;
                })
                .catch((error) => {
                    pdfJsLoadingPromise = null;
                    throw new Error('Не удалось загрузить PDF-конвертер в браузере. Проверьте подключение к интернету и попробуйте снова.');
                });
        }

        return pdfJsLoadingPromise;
    }

    function renderScanResults(results) {
        const container = document.getElementById('scanResults');

        if (!results.length) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = results.map((result) => `
            <article class="rounded-2xl border ${result.status === 'incomplete_scan' ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50'} p-4">
                <div class="flex flex-wrap justify-between gap-3">
                    <div>
                        <div class="font-semibold ${result.status === 'incomplete_scan' ? 'text-amber-900' : 'text-emerald-900'}">${escapeHtml(result.student_name || 'Без имени')}</div>
                        <div class="text-sm ${result.status === 'incomplete_scan' ? 'text-amber-800' : 'text-emerald-800'} mt-1">${escapeHtml(result.file_name || '')}</div>
                        ${result.expected_pages ? `<div class="text-xs mt-2 ${result.status === 'incomplete_scan' ? 'text-amber-700' : 'text-emerald-700'}">Листы: ${(result.pages_processed || []).join(', ') || '—'} из ${result.expected_pages}</div>` : ''}
                    </div>
                    <div class="text-right">
                        <div class="font-semibold ${result.status === 'incomplete_scan' ? 'text-amber-900' : 'text-emerald-900'}">${result.score ?? '—'} / ${result.max_score ?? '—'}</div>
                        <div class="text-sm ${result.status === 'incomplete_scan' ? 'text-amber-800' : 'text-emerald-800'}">${escapeHtml(result.grade || (result.status === 'incomplete_scan' ? 'Нужно загрузить все листы' : ''))}</div>
                    </div>
                </div>
                ${result.warnings?.length ? `
                    <div class="mt-3 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-3">
                        ${result.warnings.map((warning) => escapeHtml(warning)).join('<br>')}
                    </div>
                ` : ''}
            </article>
        `).join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function formatDate(value) {
        if (!value) {
            return '';
        }

        return new Date(value).toLocaleDateString('ru-RU');
    }

    document.addEventListener('DOMContentLoaded', async () => {
        if (!await ensureAuthenticatedPage()) {
            return;
        }

        syncBlankGenerationModeButtons();
        loadPage();
    });
</script>
</body>
</html>
