<!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Вход'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<main class="relative overflow-hidden">
    <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-16">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.06fr)_430px] lg:items-center">
            <section class="space-y-8 py-4 lg:py-8">
                <div class="space-y-5">
                    <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50/90 px-4 py-2 text-sm font-medium text-sky-700 shadow-sm dark:border-sky-500/20 dark:bg-slate-900/80 dark:text-sky-200">
                        <span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                        Один кабинет для тестов, бланков и проверки сканов
                    </div>

                    <div class="space-y-4">
                        <h1 class="max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl lg:text-6xl dark:text-white">
                            Вход в рабочий кабинет Провериума.
                        </h1>
                        <p class="max-w-2xl text-base leading-7 text-slate-600 sm:text-lg dark:text-slate-300">
                            После входа можно сразу перейти к созданию тестов, выпуску бланков, загрузке сканов и журналу группы без переключения между разными сервисами.
                        </p>
                    </div>
                </div>

                <div class="grid max-w-2xl gap-4">
                    <div class="flex items-start gap-4 border-l border-sky-600/25 pl-4 dark:border-sky-400/25">
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/90 text-sky-700 shadow-sm ring-1 ring-black/5 dark:bg-slate-900 dark:text-sky-200 dark:ring-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Тесты и варианты</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-400">Управление вопросами, шкалой оценивания и вариантами из одного кабинета.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 border-l border-sky-600/25 pl-4 dark:border-sky-400/25">
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/90 text-sky-700 shadow-sm ring-1 ring-black/5 dark:bg-slate-900 dark:text-sky-200 dark:ring-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M6 7v11a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7M9 11h.01M12 11h.01M15 11h.01M9 15h.01M12 15h.01M15 15h.01"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">OCR и авторазбор</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-400">Результат проверки собирается в системе и сразу готов к просмотру и выгрузке.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 border-l border-sky-600/25 pl-4 dark:border-sky-400/25">
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/90 text-sky-700 shadow-sm ring-1 ring-black/5 dark:bg-slate-900 dark:text-sky-200 dark:ring-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V8H2v12h5m10 0v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4m10 0H7"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Группы и журнал</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-400">Списки студентов, печать бланков и журнал оценок связаны в одном потоке работы.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="proverium-panel rounded-[2rem] border border-white/70 p-6 sm:p-8 dark:border-white/10">
                <div class="mb-8">
                    <div class="inline-flex items-center gap-3 text-[#14387a] dark:text-slate-50">
                        <img src="{{ asset('brand/proverium-mark.svg') }}" alt="Провериум" class="h-11 w-11 rounded-2xl shadow-halo">
                        <div class="flex min-w-0 flex-col gap-0.5">
                            <span class="truncate text-xl font-black uppercase tracking-tight">Провериум</span>
                        </div>
                    </div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">Вход в кабинет</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">
                        Введите email и пароль, чтобы открыть ваш рабочий контур.
                    </p>
                </div>

                <form id="loginForm" class="space-y-6">
                    <div id="errorMessage" class="hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200"></div>
                    <div id="successMessage" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200"></div>

                    <div class="space-y-4">
                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                            <input id="email"
                                   name="email"
                                   type="email"
                                   autocomplete="email"
                                   required
                                   class="w-full rounded-2xl border border-slate-300 bg-white/85 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-100 dark:focus:border-sky-400"
                                   placeholder="Введите email">
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Пароль</label>
                            <input id="password"
                                   name="password"
                                   type="password"
                                   autocomplete="current-password"
                                   required
                                   class="w-full rounded-2xl border border-slate-300 bg-white/85 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-500/30 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-100 dark:focus:border-sky-400"
                                   placeholder="Введите пароль">
                        </div>
                    </div>

                    <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 py-3 text-sm font-semibold text-white shadow-[0_18px_34px_-22px_rgba(2,132,199,0.75)] transition hover:bg-sky-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM5 21a7 7 0 0 1 14 0"/>
                        </svg>
                        Войти
                    </button>

                    <p class="text-center text-sm text-slate-600 dark:text-slate-400">
                        Нет аккаунта?
                        <a href="/user/register" class="font-semibold text-sky-600 transition hover:text-sky-700 dark:text-sky-300 dark:hover:text-sky-200">
                            Создать профиль
                        </a>
                    </p>
                </form>
            </section>
        </div>
    </div>
</main>

<script>
    const loginParams = new URLSearchParams(window.location.search);

    function showMessage(type, message) {
        const errorDiv = document.getElementById('errorMessage');
        const successDiv = document.getElementById('successMessage');

        if (type === 'error') {
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
            successDiv.classList.add('hidden');
        } else {
            successDiv.textContent = message;
            successDiv.classList.remove('hidden');
            errorDiv.classList.add('hidden');
        }
    }

    document.getElementById('loginForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const formData = {
            email: document.getElementById('email').value.trim().toLowerCase(),
            password: document.getElementById('password').value
        };

        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok) {
                setAuthState(data);
                showMessage('success', data.message || 'Вход выполнен.');

                setTimeout(() => {
                    const nextUrl = loginParams.get('next') || '/user/profile';
                    window.location.href = nextUrl;
                }, 900);
            } else if (data.errors) {
                const errorMessages = Object.values(data.errors).flat().join(', ');
                showMessage('error', errorMessages);
            } else {
                showMessage('error', data.message || 'Не удалось выполнить вход.');
            }
        } catch (error) {
            console.error('Login error:', error);
            showMessage('error', 'Ошибка соединения с сервером.');
        }
    });

    document.addEventListener('DOMContentLoaded', async () => {
        const reason = loginParams.get('reason');
        if (reason === 'expired') {
            showMessage('error', 'Сессия истекла. Войдите снова.');
        } else if (reason === 'missing') {
            showMessage('error', 'Для продолжения нужно войти в кабинет.');
        }

        await redirectAuthenticatedUser('/user/profile');
    });
</script>
</body>
</html>
