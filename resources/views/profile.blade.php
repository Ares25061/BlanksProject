{{-- resources/views/user/profile.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль | ТестСистема</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-100 min-h-screen">

@include('layouts.nav')

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <!-- Индикатор загрузки -->
    <div id="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
        <p class="text-gray-600 mt-4">Загрузка данных профиля...</p>
    </div>

    <!-- Основной контент -->
    <div id="profileContent" class="hidden"></div>

    <!-- Ошибка -->
    <div id="errorContent" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        Ошибка загрузки профиля. <a href="/user/login" class="underline">Войдите</a> заново.
    </div>
</div>

<script>
    function formatDate(dateString) {
        if (!dateString) return 'Не указано';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return 'Ошибка даты';
        }
    }

    async function loadProfile() {
        try {
            if (!await ensureAuthenticatedPage()) {
                return;
            }

            let user = getStoredUser();
            if (!user?.id) {
                const refreshed = await refreshAuthToken({ suppressRedirect: true });
                if (!refreshed) {
                    clearAuthState();
                    redirectToLogin('expired');
                    return;
                }

                user = getStoredUser();
            }

            if (!user?.id) {
                clearAuthState();
                redirectToLogin('missing');
                return;
            }

            const response = await authApiFetch(`/api/user/${user.id}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (response.ok) {
                const data = await response.json();
                displayProfile(data.user);
            } else if (response.status === 404) {
                showError('Пользователь не найден');
            } else {
                showError('Ошибка загрузки профиля');
            }
        } catch (error) {
            console.error('Error loading profile:', error);
            showError('Ошибка соединения с сервером');
        }
    }

    function displayProfile(user) {
        document.getElementById('loading').classList.add('hidden');
        const profileContent = document.getElementById('profileContent');
        profileContent.classList.remove('hidden');

        const initials = user.name ? user.name.charAt(0).toUpperCase() : 'U';

        profileContent.innerHTML = `
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-8 text-white">
                    <div class="flex flex-col md:flex-row items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-24 h-24 rounded-full bg-white flex items-center justify-center text-3xl font-bold text-blue-600 shadow-lg">
                                ${initials}
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold">${user.name}</h1>
                                <p class="opacity-80">${user.email}</p>
                                <span class="text-sm px-2 py-1 rounded bg-white bg-opacity-20 mt-2 inline-block">
                                    ID: ${user.id}
                                </span>
                            </div>
                        </div>
                        <button onclick="logout()" class="mt-4 md:mt-0 bg-white text-blue-600 px-4 py-2 rounded-lg font-bold shadow hover:bg-gray-100 transition">
                            Выйти
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2">Личная информация</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="border p-4 rounded-lg shadow-sm">
                                <p class="text-xs text-gray-500 uppercase">Имя</p>
                                <p class="text-lg font-semibold">${user.name}</p>
                            </div>
                            <div class="border p-4 rounded-lg shadow-sm">
                                <p class="text-xs text-gray-500 uppercase">Email</p>
                                <p class="text-lg font-semibold">${user.email}</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="border p-4 rounded-lg shadow-sm">
                                <p class="text-xs text-gray-500 uppercase">Дата регистрации</p>
                                <p class="font-semibold">${formatDate(user.created_at)}</p>
                            </div>
                            <div class="border p-4 rounded-lg shadow-sm">
                                <p class="text-xs text-gray-500 uppercase">Статус email</p>
                                <p class="font-bold ${user.email_verified_at ? 'text-green-600' : 'text-orange-600'}">
                                    ${user.email_verified_at ? 'Подтвержден' : 'Ожидает подтверждения'}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t flex flex-wrap gap-4 justify-center md:justify-start">
                        <a href="/user/edit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                            Редактировать профиль
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-4 rounded-lg shadow border text-center">
                    <h3 class="text-gray-500 text-sm">Дней в системе</h3>
                    <p class="text-3xl font-bold text-blue-600">
                        ${Math.floor((new Date() - new Date(user.created_at)) / (1000 * 60 * 60 * 24))}
                    </p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow border text-center">
                    <h3 class="text-gray-500 text-sm">Тестов создано</h3>
                    <p class="text-3xl font-bold text-green-600">0</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow border text-center">
                    <h3 class="text-gray-500 text-sm">ID аккаунта</h3>
                    <p class="text-xl font-bold text-gray-700">#${user.id}</p>
                </div>
            </div>
        `;
    }

    function showError(message = 'Ошибка загрузки профиля') {
        document.getElementById('loading').classList.add('hidden');
        const errorContent = document.getElementById('errorContent');
        errorContent.classList.remove('hidden');
        errorContent.innerHTML = message + ' <a href="/user/login" class="underline">Войдите</a> заново.';
    }

    async function logout() {
        try {
            await authApiFetch('/api/logout', { method: 'POST' }, { suppressRedirect: true });
        } catch (e) {
            console.error('Logout error:', e);
        } finally {
            clearAuthState();
            window.location.href = '/user/login';
        }
    }

    document.addEventListener('DOMContentLoaded', loadProfile);
</script>
</body>
</html>
