{{-- resources/views/user/profile.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Профиль'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <!-- Индикатор загрузки -->
    <div id="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
        <p class="text-gray-600 mt-4 dark:text-slate-300">Загрузка данных профиля...</p>
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

        const testsCount = Number(user.tests_count) || 0;
        const groupsCount = Number(user.groups_count) || 0;
        const daysInSystem = Math.max(0, Math.floor((new Date() - new Date(user.created_at)) / (1000 * 60 * 60 * 24)));

        profileContent.innerHTML = `
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="border-b border-slate-200 bg-[linear-gradient(135deg,#f7f8ff,#eef4ff,#eefbf8)] p-8 text-slate-900 dark:border-slate-800 dark:bg-[linear-gradient(135deg,#0f172a,#1e1b4b,#0f172a)] dark:text-white">
                    <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-3">
                            <div class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-white/70">Профиль</div>
                            <div>
                                <h1 class="text-3xl font-black tracking-tight">${user.name}</h1>
                                <p class="mt-2 text-base text-slate-600 dark:text-white/80">${user.email}</p>
                            </div>
                    <span class="inline-flex rounded-full border border-slate-200 bg-white/90 px-3 py-1 text-sm font-medium text-slate-700 dark:border-white/10 dark:bg-slate-900/90 dark:text-white">
                                ID: ${user.id}
                            </span>
                        </div>
                        <button onclick="logout()" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:border dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                            Выйти
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <h2 class="mb-6 border-b border-slate-200 pb-2 text-xl font-bold text-slate-900 dark:border-slate-800 dark:text-white">Личная информация</h2>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950 dark:shadow-none">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500 dark:text-slate-500">Имя</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">${user.name}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950 dark:shadow-none">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500 dark:text-slate-500">Email</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-slate-100">${user.email}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950 dark:shadow-none">
                            <p class="text-xs uppercase tracking-[0.25em] text-slate-500 dark:text-slate-500">Дата регистрации</p>
                            <p class="mt-2 font-semibold text-slate-900 dark:text-slate-100">${formatDate(user.created_at)}</p>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-center border-t border-slate-200 pt-6 dark:border-slate-800">
                        <a href="/user/edit" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-6 py-3 font-medium text-white transition hover:bg-blue-700">
                            Редактировать профиль
                        </a>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <h3 class="text-sm uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Дней в системе</h3>
                    <p class="mt-3 text-3xl font-bold text-blue-600">${daysInSystem}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <h3 class="text-sm uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Тестов создано</h3>
                    <p class="mt-3 text-3xl font-bold text-emerald-600">${testsCount}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <h3 class="text-sm uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">Количество групп</h3>
                    <p class="mt-3 text-3xl font-bold text-violet-600">${groupsCount}</p>
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
