<!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Редактирование профиля'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<div class="container mx-auto max-w-6xl px-4 py-8">
    <div id="loading" class="py-12 text-center">
        <div class="inline-block h-12 w-12 animate-spin rounded-full border-b-2 border-t-2 border-blue-500"></div>
        <p class="mt-4 text-slate-600 dark:text-slate-300">Загрузка редактора профиля...</p>
    </div>

    <div id="pageContent" class="hidden">
        <div class="mb-8">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-sky-700">Профиль</p>
            <h1 class="mt-2 text-3xl font-bold">Редактирование профиля</h1>
            <p class="mt-2 text-slate-600 dark:text-slate-400">Измените имя и email. Обновлённые данные сразу попадут в кабинет и навигацию.</p>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <form id="profileForm" class="space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold">Личные данные</h2>
                            <p class="mt-1 text-slate-500 dark:text-slate-400">Сохраните актуальное имя и рабочий email для входа.</p>
                        </div>
                        <a href="/user/profile" class="inline-flex items-center justify-center rounded-2xl bg-slate-700 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700">
                            Вернуться в профиль
                        </a>
                    </div>

                    <div id="successMessage" class="mt-5 hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200"></div>
                    <div id="errorMessage" class="mt-5 hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200"></div>

                    <div class="mt-6 grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="name" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Имя</label>
                            <input id="name" type="text" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-500" placeholder="Ваше имя">
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                            <input id="email" type="email" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-sky-500 focus:ring-2 focus:ring-sky-500" placeholder="mail@example.com">
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6 dark:border-slate-800">
                        <a href="/user/profile" class="inline-flex items-center justify-center rounded-2xl bg-slate-700 px-5 py-3 font-medium text-white transition hover:bg-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700">
                            Отмена
                        </a>
                        <button id="saveButton" type="submit" class="inline-flex items-center justify-center rounded-2xl bg-sky-600 px-5 py-3 font-medium text-white transition hover:bg-sky-500">
                            Сохранить изменения
                        </button>
                    </div>
                </section>
            </form>

            <aside class="space-y-4 xl:sticky xl:top-24">
                <section class="proverium-panel rounded-3xl border border-slate-200 p-5 dark:border-slate-800">
                    <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Предпросмотр</div>
                    <div class="mt-4 space-y-3">
                        <div class="rounded-2xl bg-white/80 p-4 dark:bg-slate-950/80">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Имя</div>
                            <div id="previewName" class="mt-2 text-lg font-semibold text-slate-900 dark:text-white">—</div>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-4 dark:bg-slate-950/80">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Email</div>
                            <div id="previewEmail" class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">—</div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Справка</div>
                    <div class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">ID</div>
                            <div id="profileMetaId" class="mt-2 font-semibold text-slate-900 dark:text-white">—</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Дата регистрации</div>
                            <div id="profileMetaCreatedAt" class="mt-2 font-semibold text-slate-900 dark:text-white">—</div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>

    <div id="errorContent" class="hidden rounded-2xl border border-red-300 bg-red-50 px-4 py-3 text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200">
        Не удалось открыть редактор профиля. <a href="/user/profile" class="underline">Вернуться в профиль</a>.
    </div>
</div>

<script>
    let currentUser = null;

    function formatDate(dateString) {
        if (!dateString) {
            return 'Не указано';
        }

        try {
            return new Date(dateString).toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return 'Ошибка даты';
        }
    }

    function setPreviewState() {
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();

        document.getElementById('previewName').textContent = name || 'Без имени';
        document.getElementById('previewEmail').textContent = email || 'Email не указан';
    }

    function fillProfileForm(user) {
        currentUser = user;
        document.getElementById('name').value = user.name || '';
        document.getElementById('email').value = user.email || '';
        document.getElementById('profileMetaId').textContent = user.id ? `ID: ${user.id}` : '—';
        document.getElementById('profileMetaCreatedAt').textContent = formatDate(user.created_at);
        setPreviewState();
    }

    function showErrorState(message = 'Не удалось открыть редактор профиля.') {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('pageContent').classList.add('hidden');
        const errorContent = document.getElementById('errorContent');
        errorContent.classList.remove('hidden');
        errorContent.innerHTML = `${message} <a href="/user/profile" class="underline">Вернуться в профиль</a>.`;
    }

    function setFormMessage(type, message = '') {
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

    async function loadEditableProfile() {
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

            if (!response.ok) {
                throw new Error('Не удалось загрузить профиль');
            }

            const payload = await response.json();
            fillProfileForm(payload.user);
            document.getElementById('loading').classList.add('hidden');
            document.getElementById('pageContent').classList.remove('hidden');
        } catch (error) {
            console.error(error);
            showErrorState(error.message || 'Не удалось открыть редактор профиля.');
        }
    }

    document.getElementById('profileForm').addEventListener('submit', async (event) => {
        event.preventDefault();

        const saveButton = document.getElementById('saveButton');
        const payload = {
            name: document.getElementById('name').value.trim(),
            email: document.getElementById('email').value.trim(),
        };

        if (!payload.name) {
            setFormMessage('error', 'Укажите имя.');
            return;
        }

        if (!payload.email) {
            setFormMessage('error', 'Укажите email.');
            return;
        }

        try {
            setFormMessage();
            saveButton.disabled = true;
            saveButton.classList.add('opacity-70', 'cursor-wait');
            saveButton.textContent = 'Сохраняю...';

            const response = await authApiFetch('/api/user/edit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = data.message || data.errors?.email?.[0] || data.errors?.name?.[0] || 'Не удалось сохранить изменения.';
                throw new Error(message);
            }

            currentUser = data.user || currentUser;
            setAuthState({ user: currentUser });
            fillProfileForm(currentUser);
            setFormMessage('success', 'Профиль обновлён.');
        } catch (error) {
            setFormMessage('error', error.message || 'Не удалось сохранить изменения.');
        } finally {
            saveButton.disabled = false;
            saveButton.classList.remove('opacity-70', 'cursor-wait');
            saveButton.textContent = 'Сохранить изменения';
        }
    });

    document.getElementById('name').addEventListener('input', setPreviewState);
    document.getElementById('email').addEventListener('input', setPreviewState);
    document.addEventListener('DOMContentLoaded', loadEditableProfile);
</script>
</body>
</html>
