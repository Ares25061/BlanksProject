<!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Тесты'])
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex flex-wrap justify-between items-center gap-4 mb-8">
        <div>
            <p class="text-sm uppercase tracking-[0.3em] text-sky-700 font-semibold">Рабочее место преподавателя</p>
            <h1 class="text-3xl font-bold text-slate-900 mt-2 dark:text-white">Мои тесты</h1>
            <p class="text-slate-600 mt-2 dark:text-slate-400">Создавайте тесты, задавайте критерии оценивания, печатайте персональные бланки и проверяйте сканы.</p>
        </div>

        <div class="flex flex-wrap gap-3">
            <button onclick="window.location.href='/groups'" class="bg-white text-slate-800 px-5 py-3 rounded-xl border border-slate-200 hover:border-sky-300 hover:shadow-sm transition flex items-center gap-2 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-100 dark:hover:border-sky-400 dark:hover:shadow-none">
                <i class="fas fa-users"></i>
                Группы
            </button>
            <button onclick="createTest()" class="bg-sky-600 text-white px-6 py-3 rounded-xl hover:bg-sky-500 transition flex items-center gap-2 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Создать тест
            </button>
        </div>
    </div>

    <div id="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sky-500"></div>
        <p class="text-slate-600 mt-4 dark:text-slate-300">Загружаю тесты...</p>
    </div>

    <div id="testsList" class="hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"></div>

    <div id="emptyList" class="hidden text-center py-16 bg-white rounded-2xl shadow-sm border border-slate-200 dark:bg-slate-900 dark:border-slate-800 dark:shadow-none">
        <svg class="w-24 h-24 text-slate-300 mx-auto mb-4 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <h3 class="text-xl font-semibold text-slate-700 mb-2 dark:text-white">Тестов пока нет</h3>
        <p class="text-slate-500 mb-6 dark:text-slate-400">Создайте первый тест и подготовьте персональные бланки для своих групп.</p>
        <button onclick="createTest()" class="bg-sky-600 text-white px-6 py-3 rounded-xl hover:bg-sky-500 transition">
            Создать тест
        </button>
    </div>

    <div id="errorMessage" class="hidden bg-rose-100 border border-rose-300 text-rose-700 px-4 py-3 rounded-xl"></div>
</div>

<script>
    async function apiFetch(url, options = {}) {
        return authApiFetch(url, options);
    }

    async function loadTests() {
        try {
            const response = await apiFetch('/api/tests');

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Не удалось загрузить тесты');
            }

            const data = await response.json();
            const tests = data.data?.data || data.data || [];
            displayTests(tests);
        } catch (error) {
            console.error(error);
            showError(error.message || 'Ошибка соединения с сервером');
        }
    }

    function displayTests(tests) {
        document.getElementById('loading').classList.add('hidden');

        if (!tests.length) {
            document.getElementById('emptyList').classList.remove('hidden');
            return;
        }

        const testsList = document.getElementById('testsList');
        testsList.classList.remove('hidden');
        testsList.innerHTML = tests.map((test) => {
            const totalPoints = (test.questions || []).reduce((sum, question) => sum + (question.points || 0), 0);
            const gradeCriteriaCount = (test.grade_criteria || []).length;
            const deliveryModeLabel = getDeliveryModeLabel(test.delivery_mode);
            const testStatus = getTestStatusValue(test);
            const testStatusLabel = getTestStatusLabel(test);
            const canClose = testStatus !== 'closed';

            return `
                <article class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition overflow-hidden dark:bg-slate-900 dark:border-slate-800 dark:shadow-none dark:hover:shadow-none">
                    <div class="h-2 ${getStatusAccentClass(testStatus)}"></div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-start gap-4">
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900 dark:text-white">${escapeHtml(test.title || 'Без названия')}</h3>
                                <p class="text-slate-500 mt-2 dark:text-slate-400">${escapeHtml(test.description || 'Описание не заполнено')}</p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="px-3 py-1 text-xs rounded-full ${getStatusBadgeClass(testStatus)}">
                                    ${testStatusLabel}
                                </span>
                                <span class="px-3 py-1 text-xs rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                                    ${deliveryModeLabel}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm text-slate-600 dark:text-slate-300">
                            <div class="bg-slate-50 rounded-xl px-4 py-3 dark:bg-slate-950/70">
                                <div class="text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500">Вопросы</div>
                                <div class="font-semibold text-slate-800 mt-1 dark:text-white">${test.questions?.length || 0}</div>
                            </div>
                            <div class="bg-slate-50 rounded-xl px-4 py-3 dark:bg-slate-950/70">
                                <div class="text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500">Макс. балл</div>
                                <div class="font-semibold text-slate-800 mt-1 dark:text-white">${totalPoints}</div>
                            </div>
                            <div class="bg-slate-50 rounded-xl px-4 py-3 dark:bg-slate-950/70">
                                <div class="text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500">Оценки</div>
                                <div class="font-semibold text-slate-800 mt-1 dark:text-white">${gradeCriteriaCount}</div>
                            </div>
                            <div class="bg-slate-50 rounded-xl px-4 py-3 dark:bg-slate-950/70">
                                <div class="text-xs uppercase tracking-wider text-slate-400 dark:text-slate-500">Время</div>
                                <div class="font-semibold text-slate-800 mt-1 dark:text-white">${test.time_limit ? `${test.time_limit} мин` : 'Без лимита'}</div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-400 dark:text-slate-500">
                                ${test.created_at ? new Date(test.created_at).toLocaleDateString('ru-RU') : ''}
                            </span>
                            <div class="flex items-center gap-2 text-lg">
                                <button onclick="viewTest(${test.id})" class="text-sky-600 hover:text-sky-800" title="Открыть тест">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="manageWorkflow(${test.id})" class="text-emerald-600 hover:text-emerald-800" title="Бланки и сканирование">
                                    <i class="fas fa-layer-group"></i>
                                </button>
                                ${canClose ? `
                                    <button onclick="closeTest(${test.id})" class="text-amber-500 hover:text-amber-700" title="Завершить тест">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                ` : ''}
                                <button onclick="deleteTest(${test.id})" class="text-rose-600 hover:text-rose-800" title="Удалить">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            `;
        }).join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function createTest() {
        window.location.href = '/tests/create';
    }

    function getTestStatusValue(test) {
        return test?.test_status || (test?.is_active ? 'active' : 'draft');
    }

    function getTestStatusLabel(test) {
        return test?.test_status_label || (
            getTestStatusValue(test) === 'closed'
                ? 'Закрыт'
                : getTestStatusValue(test) === 'draft'
                    ? 'Черновик'
                    : 'Активен'
        );
    }

    function getDeliveryModeLabel(mode) {
        if (mode === 'electronic') {
            return 'Электронный';
        }

        if (mode === 'hybrid') {
            return 'Совмещённый';
        }

        return 'Бланки';
    }

    function getStatusAccentClass(status) {
        switch (status) {
            case 'closed':
                return 'bg-rose-500';
            case 'draft':
                return 'bg-slate-300';
            default:
                return 'bg-emerald-500';
        }
    }

    function getStatusBadgeClass(status) {
        switch (status) {
            case 'closed':
                return 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300';
            case 'draft':
                return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
            default:
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300';
        }
    }

    function viewTest(id) {
        window.location.href = `/tests/${id}`;
    }

    function manageWorkflow(id) {
        window.location.href = `/tests/${id}#workflow`;
    }

    async function deleteTest(id) {
        if (!confirm('Удалить этот тест?')) {
            return;
        }

        try {
            const response = await apiFetch(`/api/tests/${id}`, { method: 'DELETE' });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Ошибка удаления');
            }

            document.getElementById('testsList').classList.add('hidden');
            document.getElementById('loading').classList.remove('hidden');
            loadTests();
        } catch (error) {
            alert(error.message || 'Ошибка соединения с сервером');
        }
    }

    async function closeTest(id) {
        if (!confirm('Завершить тест? После этого его нельзя будет проходить и выпускать для него новые бланки.')) {
            return;
        }

        try {
            const response = await apiFetch(`/api/tests/${id}/close`, {
                method: 'PATCH'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                const message = errorData.errors ? Object.values(errorData.errors).flat().join(', ') : errorData.message;
                throw new Error(message || 'Не удалось завершить тест');
            }

            document.getElementById('testsList').classList.add('hidden');
            document.getElementById('loading').classList.remove('hidden');
            await loadTests();
        } catch (error) {
            alert(error.message || 'Ошибка завершения теста');
        }
    }

    function showError(message) {
        document.getElementById('loading').classList.add('hidden');
        const errorMessage = document.getElementById('errorMessage');
        errorMessage.textContent = message;
        errorMessage.classList.remove('hidden');
    }

    document.addEventListener('DOMContentLoaded', async () => {
        if (!await ensureAuthenticatedPage()) {
            return;
        }

        loadTests();
    });
</script>
</body>
</html>
