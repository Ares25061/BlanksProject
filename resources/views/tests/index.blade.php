{{-- resources/views/tests/index.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тесты | BlanksProject</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 min-h-screen">

@include('layouts.nav')

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <!-- Заголовок и кнопка создания -->
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Мои тесты</h1>
        <button onclick="createTest()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Создать тест
        </button>
    </div>

    <!-- Индикатор загрузки -->
    <div id="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
        <p class="text-gray-600 mt-4">Загрузка тестов...</p>
    </div>

    <!-- Список тестов -->
    <div id="testsList" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>

    <!-- Пустой список -->
    <div id="emptyList" class="hidden text-center py-16 bg-white rounded-lg shadow">
        <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">У вас пока нет тестов</h3>
        <p class="text-gray-500 mb-6">Создайте свой первый тест, чтобы начать</p>
        <button onclick="createTest()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
            Создать тест
        </button>
    </div>

    <!-- Ошибка -->
    <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>
</div>

<script>
    // Проверка авторизации при загрузке
    if (!localStorage.getItem('auth_token')) {
        window.location.href = '/user/login';
    }

    const token = localStorage.getItem('auth_token');
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    // Загрузка тестов
    async function loadTests() {
        try {
            console.log('Loading tests...');
            const response = await fetch('/api/tests', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            console.log('Response status:', response.status);

            if (response.ok) {
                const data = await response.json();
                console.log('Response data:', data);

                // Проверяем структуру ответа
                let tests = [];
                if (data.data && data.data.data && Array.isArray(data.data.data)) {
                    tests = data.data.data;
                } else if (data.data && Array.isArray(data.data)) {
                    tests = data.data;
                } else if (Array.isArray(data)) {
                    tests = data;
                }

                console.log('Tests array:', tests);
                displayTests(tests);
            } else if (response.status === 401) {
                console.log('Token expired, attempting refresh...');
                const refreshResult = await refreshToken();
                if (refreshResult) {
                    loadTests();
                } else {
                    window.location.href = '/user/login';
                }
            } else {
                const errorData = await response.json();
                console.error('Error response:', errorData);
                showError('Ошибка загрузки тестов: ' + (errorData.message || 'Неизвестная ошибка'));
            }
        } catch (error) {
            console.error('Error loading tests:', error);
            showError('Ошибка соединения с сервером');
        }
    }

    // Отображение тестов
    function displayTests(tests) {
        console.log('Displaying tests:', tests);
        document.getElementById('loading').classList.add('hidden');

        if (!tests || tests.length === 0) {
            console.log('No tests found');
            document.getElementById('emptyList').classList.remove('hidden');
            return;
        }

        const testsList = document.getElementById('testsList');
        testsList.classList.remove('hidden');
        document.getElementById('emptyList').classList.add('hidden');

        testsList.innerHTML = tests.map(test => {
            console.log('Processing test:', test);
            return `
                <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-semibold text-gray-800">${escapeHtml(test.title || 'Без названия')}</h3>
                            <span class="px-2 py-1 text-xs rounded-full ${test.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                ${test.is_active ? 'Активен' : 'Неактивен'}
                            </span>
                        </div>

                        <p class="text-gray-600 mb-4 line-clamp-2">${escapeHtml(test.description || 'Нет описания')}</p>

                        <div class="flex items-center text-sm text-gray-500 mb-4">
                            <span class="mr-4">
                                <i class="far fa-clock mr-1"></i>
                                ${test.time_limit ? test.time_limit + ' мин' : 'Без ограничений'}
                            </span>
                            <span>
                                <i class="far fa-question-circle mr-1"></i>
                                ${test.questions?.length || 0} вопросов
                            </span>
                        </div>

                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-400">
                                Создан: ${test.created_at ? new Date(test.created_at).toLocaleDateString() : 'Неизвестно'}
                            </span>
                            <div class="space-x-2">
                                <button onclick="viewTest(${test.id})" class="text-blue-600 hover:text-blue-800" title="Просмотреть">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="printTest(${test.id})" class="text-green-600 hover:text-green-800" title="Распечатать бланк">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button onclick="deleteTest(${test.id})" class="text-red-600 hover:text-red-800" title="Удалить">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Функция экранирования HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Создание теста
    function createTest() {
        window.location.href = '/tests/create';
    }

    // Просмотр теста
    function viewTest(id) {
        window.location.href = `/tests/${id}`;
    }

    // Печать теста - перенаправляем на страницу печати
    function printTest(id) {
        window.location.href = `/tests/${id}/print`;
    }

    // Удаление теста
    async function deleteTest(id) {
        if (!confirm('Вы уверены, что хотите удалить этот тест?')) {
            return;
        }

        try {
            const response = await fetch(`/api/tests/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                // Перезагружаем список тестов
                document.getElementById('loading').classList.remove('hidden');
                document.getElementById('testsList').classList.add('hidden');
                document.getElementById('emptyList').classList.add('hidden');
                loadTests();
            } else {
                const error = await response.json();
                alert('Ошибка при удалении теста: ' + (error.message || 'Неизвестная ошибка'));
            }
        } catch (error) {
            console.error('Error deleting test:', error);
            alert('Ошибка соединения с сервером');
        }
    }

    // Обновление токена
    async function refreshToken() {
        try {
            console.log('Refreshing token...');
            const response = await fetch('/api/refresh', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                console.log('Refresh response:', data);
                const newToken = data.authorization?.token || data.token;
                if (newToken) {
                    localStorage.setItem('auth_token', newToken);
                }
                if (data.user) {
                    localStorage.setItem('user', JSON.stringify(data.user));
                }
                return true;
            }
            return false;
        } catch (error) {
            console.error('Refresh error:', error);
            return false;
        }
    }

    function showError(message) {
        document.getElementById('loading').classList.add('hidden');
        const errorMsg = document.getElementById('errorMessage');
        errorMsg.textContent = message;
        errorMsg.classList.remove('hidden');

        setTimeout(() => {
            errorMsg.classList.add('hidden');
        }, 5000);
    }

    // Загружаем тесты при загрузке страницы
    document.addEventListener('DOMContentLoaded', loadTests);
</script>
</body>
</html>
