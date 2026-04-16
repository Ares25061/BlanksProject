<!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Группы'])
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid lg:grid-cols-[380px_1fr] gap-6 items-start">
        <section class="bg-white border border-slate-200 shadow-sm rounded-3xl p-6 sticky top-28 dark:bg-slate-900 dark:border-slate-800">
            <div class="mb-6">
                <p class="text-sm uppercase tracking-[0.25em] text-sky-700 font-semibold">Учебные группы</p>
                <h1 class="text-2xl font-bold mt-2" id="formTitle">Новая группа</h1>
                <p class="text-slate-500 mt-2 dark:text-slate-400">Укажите название группы и список студентов: один ученик на строку, полное ФИО.</p>
            </div>

            <form id="groupForm" class="space-y-4">
                <input type="hidden" id="groupId">

                <div>
                    <label for="groupName" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Название группы</label>
                    <input id="groupName" type="text" required
                           class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                           placeholder="Например: 22ИС4-1">
                </div>

                <div>
                    <label for="groupDescription" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Описание</label>
                    <input id="groupDescription" type="text"
                           class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                           placeholder="Классный руководитель, предмет или комментарий">
                </div>

                <div>
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                        <label for="studentsInput" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Список студентов</label>
                        <div class="flex flex-wrap gap-2">
                            <input id="studentsImportInput" type="file" accept=".txt,.csv,.tsv" class="hidden">
                            <button type="button" onclick="triggerStudentsImport()" class="bg-white border border-slate-200 text-slate-700 px-3 py-2 rounded-xl hover:border-sky-300 hover:text-sky-700 transition text-sm dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                                Импорт списка
                            </button>
                            <button type="button" onclick="exportCurrentStudents()" class="bg-white border border-slate-200 text-slate-700 px-3 py-2 rounded-xl hover:border-sky-300 hover:text-sky-700 transition text-sm dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                                Экспорт из формы
                            </button>
                        </div>
                    </div>
                    <textarea id="studentsInput" rows="12" required
                              class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                              placeholder="Дудина Софья Романовна&#10;Петров Иван Сергеевич&#10;..."></textarea>
                    <p class="text-xs text-slate-500 mt-2 dark:text-slate-400">Поддерживаются списки в `.txt`, `.csv` и `.tsv`. Пустые строки будут проигнорированы.</p>
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="submit" class="bg-sky-600 text-white px-5 py-3 rounded-2xl hover:bg-sky-500 transition font-medium">
                        Сохранить группу
                    </button>
                    <button type="button" onclick="resetForm()" class="bg-slate-100 text-slate-700 px-5 py-3 rounded-2xl hover:bg-slate-200 transition font-medium dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Очистить
                    </button>
                </div>
            </form>
        </section>

        <section class="space-y-6">
            <div class="bg-white border border-slate-200 shadow-sm rounded-3xl p-6 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <h2 class="text-2xl font-bold dark:text-white">Список групп</h2>
                        <p class="text-slate-500 mt-2 dark:text-slate-400">Эти группы используются для персональных бланков и привязки результатов сканирования.</p>
                    </div>
                    <div class="w-full sm:w-80">
                        <input id="searchInput" type="text"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                               placeholder="Поиск по названию группы">
                    </div>
                </div>
            </div>

            <div id="loading" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sky-500"></div>
                <p class="text-slate-600 mt-4 dark:text-slate-300">Загружаю группы...</p>
            </div>

            <div id="groupsList" class="hidden grid xl:grid-cols-2 gap-6"></div>

            <div id="emptyState" class="hidden bg-white border border-dashed border-slate-300 rounded-3xl p-12 text-center dark:bg-slate-900 dark:border-slate-700">
                <i class="fas fa-users text-5xl text-slate-300 mb-4 dark:text-slate-600"></i>
                <h3 class="text-xl font-semibold text-slate-700 dark:text-white">Группы еще не добавлены</h3>
                <p class="text-slate-500 mt-2 dark:text-slate-400">Создайте первую группу, чтобы выпускать персональные бланки сразу на весь класс.</p>
            </div>
        </section>
    </div>
</div>

<script>
    let groups = [];

    async function apiFetch(url, options = {}) {
        return authApiFetch(url, options);
    }

    async function loadGroups() {
        try {
            const response = await apiFetch('/api/student-groups');

            if (!response.ok) {
                throw new Error('Не удалось загрузить группы');
            }

            const data = await response.json();
            groups = data.data || [];
            renderGroups(groups);
        } catch (error) {
            console.error(error);
            alert(error.message || 'Ошибка загрузки групп');
        }
    }

    function renderGroups(sourceGroups) {
        document.getElementById('loading').classList.add('hidden');

        const groupsList = document.getElementById('groupsList');
        const emptyState = document.getElementById('emptyState');

        if (!sourceGroups.length) {
            groupsList.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        groupsList.classList.remove('hidden');
        groupsList.innerHTML = sourceGroups.map((group) => `
            <article class="bg-white border border-slate-200 shadow-sm rounded-3xl p-6 dark:bg-slate-900 dark:border-slate-800">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-xl font-semibold dark:text-white">${escapeHtml(group.name)}</h3>
                            <span class="px-3 py-1 rounded-full text-xs bg-sky-100 text-sky-700">
                                ${group.students?.length || 0} студентов
                            </span>
                        </div>
                        <p class="text-slate-500 mt-2 dark:text-slate-400">${escapeHtml(group.description || 'Описание не указано')}</p>
                    </div>

                    <div class="flex items-center gap-2 text-lg">
                        <button onclick="openGradebook(${group.id})" class="text-emerald-600 hover:text-emerald-800" title="Журнал группы">
                            <i class="fas fa-table"></i>
                        </button>
                        <button onclick="exportGroupStudents(${group.id})" class="text-violet-600 hover:text-violet-800" title="Экспортировать список студентов">
                            <i class="fas fa-download"></i>
                        </button>
                        <button onclick="editGroup(${group.id})" class="text-sky-600 hover:text-sky-800" title="Редактировать">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button onclick="deleteGroup(${group.id})" class="text-rose-600 hover:text-rose-800" title="Удалить">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>

                <div class="mt-5 bg-slate-50 rounded-2xl p-4 max-h-80 overflow-auto dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 mb-3 dark:text-slate-500">Состав группы</div>
                    <ol class="space-y-2 text-sm text-slate-700 dark:text-slate-200">
                        ${(group.students || []).map((student, index) => `
                            <li class="flex gap-3 items-start">
                                <span class="text-slate-400 w-6 pt-0.5 dark:text-slate-500">${index + 1}.</span>
                                <div class="min-w-0 flex-1">
                                    <div>${escapeHtml(student.full_name)}</div>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        ${renderStudentGrades(student)}
                                    </div>
                                </div>
                            </li>
                        `).join('')}
                    </ol>
                </div>
            </article>
        `).join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function renderStudentGrades(student) {
        const grades = student.gradebook_entries || [];

        if (!grades.length) {
            return '<span class="text-xs text-slate-400 dark:text-slate-500">Оценок пока нет</span>';
        }

        return grades.slice(0, 4).map((entry) => `
            <span class="inline-flex items-center gap-2 rounded-full bg-slate-200 text-slate-700 px-3 py-1 text-xs dark:bg-slate-800 dark:text-slate-200">
                <span>${formatDate(entry.grade_date)}</span>
                <span class="font-semibold">${escapeHtml(entry.grade_value || '—')}</span>
                <span class="text-slate-500 dark:text-slate-400">${escapeHtml(entry.subject_name || 'Предмет')}</span>
            </span>
        `).join('');
    }

    function openGradebook(groupId) {
        window.location.href = `/groups/${groupId}`;
    }

    function formatDate(value) {
        if (!value) {
            return 'Без даты';
        }

        return new Date(value).toLocaleDateString('ru-RU');
    }

    function parseStudents() {
        return document.getElementById('studentsInput').value
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean);
    }

    function triggerStudentsImport() {
        document.getElementById('studentsImportInput').click();
    }

    function exportCurrentStudents() {
        const students = parseStudents();
        if (!students.length) {
            alert('В форме пока нет списка студентов для экспорта');
            return;
        }

        const fileNameBase = document.getElementById('groupName').value.trim() || 'student-list';
        downloadStudentsFile(fileNameBase, students);
    }

    function exportGroupStudents(groupId) {
        const group = groups.find((item) => item.id === groupId);
        if (!group) {
            return;
        }

        const students = (group.students || []).map((student) => student.full_name).filter(Boolean);
        if (!students.length) {
            alert('В этой группе пока нет студентов для экспорта');
            return;
        }

        downloadStudentsFile(group.name || 'student-list', students);
    }

    function downloadStudentsFile(fileNameBase, students) {
        const safeFileName = String(fileNameBase || 'student-list')
            .trim()
            .replace(/[\\/:*?"<>|]+/g, '-');
        const content = `${students.join('\n')}\n`;
        const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${safeFileName}.txt`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }

    async function handleStudentsImport(event) {
        const file = event.target.files?.[0];
        if (!file) {
            return;
        }

        try {
            const text = await file.text();
            const students = parseImportedStudents(text);

            if (!students.length) {
                throw new Error('В файле не найдено ни одной строки с ФИО');
            }

            document.getElementById('studentsInput').value = students.join('\n');
        } catch (error) {
            alert(error.message || 'Не удалось импортировать список студентов');
        } finally {
            event.target.value = '';
        }
    }

    function parseImportedStudents(text) {
        return text
            .split(/\r?\n/)
            .map((line) => normalizeImportedStudentLine(line))
            .filter(Boolean);
    }

    function normalizeImportedStudentLine(line) {
        const normalized = String(line || '').replace(/^\uFEFF/, '').trim();
        if (!normalized) {
            return '';
        }

        if (/^(фио|full_name|student|student name)$/i.test(normalized)) {
            return '';
        }

        const columns = normalized
            .split(/[;\t,]/)
            .map((value) => value.trim())
            .filter(Boolean);

        if (columns.length <= 1) {
            return normalized;
        }

        const candidate = columns.find((value) => /[A-Za-zА-Яа-яЁё]/.test(value) && !/^(фио|full_name|student|student name)$/i.test(value));
        return candidate || columns[0];
    }

    document.getElementById('groupForm').addEventListener('submit', async (event) => {
        event.preventDefault();

        const students = parseStudents();
        if (!students.length) {
            alert('Добавьте хотя бы одного студента');
            return;
        }

        const payload = {
            name: document.getElementById('groupName').value.trim(),
            description: document.getElementById('groupDescription').value.trim() || null,
            students
        };

        const groupId = document.getElementById('groupId').value;
        const isEdit = Boolean(groupId);

        try {
            const response = await apiFetch(isEdit ? `/api/student-groups/${groupId}` : '/api/student-groups', {
                method: isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Не удалось сохранить группу');
            }

            resetForm();
            loadGroups();
        } catch (error) {
            alert(error.message || 'Ошибка сохранения');
        }
    });

    function editGroup(groupId) {
        const group = groups.find((item) => item.id === groupId);
        if (!group) {
            return;
        }

        document.getElementById('formTitle').textContent = `Редактирование: ${group.name}`;
        document.getElementById('groupId').value = group.id;
        document.getElementById('groupName').value = group.name;
        document.getElementById('groupDescription').value = group.description || '';
        document.getElementById('studentsInput').value = (group.students || []).map((student) => student.full_name).join('\n');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function deleteGroup(groupId) {
        if (!confirm('Удалить группу? Все связанные персональные бланки останутся, но потеряют привязку к группе.')) {
            return;
        }

        try {
            const response = await apiFetch(`/api/student-groups/${groupId}`, {
                method: 'DELETE'
            });

            if (!response.ok) {
                throw new Error('Не удалось удалить группу');
            }

            resetForm();
            loadGroups();
        } catch (error) {
            alert(error.message || 'Ошибка удаления');
        }
    }

    function resetForm() {
        document.getElementById('formTitle').textContent = 'Новая группа';
        document.getElementById('groupId').value = '';
        document.getElementById('groupForm').reset();
    }

    document.getElementById('searchInput').addEventListener('input', (event) => {
        const query = event.target.value.trim().toLowerCase();
        const filtered = groups.filter((group) => group.name.toLowerCase().includes(query));
        renderGroups(filtered);
    });

    document.addEventListener('DOMContentLoaded', async () => {
        if (!await ensureAuthenticatedPage()) {
            return;
        }

        document.getElementById('studentsImportInput').addEventListener('change', handleStudentsImport);
        loadGroups();
    });
</script>
</body>
</html>
