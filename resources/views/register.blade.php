<!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Регистрация'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<main class="relative overflow-hidden">
    <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-16">
        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.02fr)_460px] lg:items-center">
            <section class="space-y-8 py-4 lg:py-8">
                <div class="space-y-5">
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50/90 px-4 py-2 text-sm font-medium text-indigo-700 shadow-sm dark:border-indigo-500/20 dark:bg-slate-900/80 dark:text-indigo-200">
                        <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                        Регистрация кабинета преподавателя
                    </div>

                    <div class="space-y-4">
                        <h1 class="max-w-4xl text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl lg:text-6xl dark:text-white">
                            Подключите свой контур работы за пару минут.
                        </h1>
                        <p class="max-w-2xl text-base leading-7 text-slate-600 sm:text-lg dark:text-slate-300">
                            После регистрации можно сразу создавать тесты, прикреплять группы, выпускать персональные бланки и загружать сканы на проверку.
                        </p>
                    </div>
                </div>

                <div class="grid max-w-2xl gap-4">
                    <div class="flex items-start gap-4 border-l border-indigo-600/20 pl-4 dark:border-indigo-400/20">
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/90 text-indigo-700 shadow-sm ring-1 ring-black/5 dark:bg-slate-900 dark:text-indigo-200 dark:ring-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Новый кабинет</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-400">Сразу после входа можно перейти к первому тесту и первой группе.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 border-l border-indigo-600/20 pl-4 dark:border-indigo-400/20">
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/90 text-indigo-700 shadow-sm ring-1 ring-black/5 dark:bg-slate-900 dark:text-indigo-200 dark:ring-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19h16M4 15h16M4 11h16M4 7h16"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Единый поток работы</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-400">Тест, печать, OCR и журнал по группе работают внутри одной системы.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 border-l border-indigo-600/20 pl-4 dark:border-indigo-400/20">
                        <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white/90 text-indigo-700 shadow-sm ring-1 ring-black/5 dark:bg-slate-900 dark:text-indigo-200 dark:ring-white/10">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Готово к реальной работе</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-400">Интерфейс подстраивается под системную тему и остаётся читаемым в разных браузерах.</p>
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
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">Создать аккаунт</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">
                        Укажите базовые данные. Дальше можно сразу открыть профиль и начать настройку кабинета.
                    </p>
                </div>

                <form id="registerForm" class="space-y-6">
                    <div id="errorMessage" class="hidden rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200"></div>
                    <div id="successMessage" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200"></div>

                    <div class="space-y-4">
                        <div>
                            <label for="name" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Имя</label>
                            <input id="name"
                                   name="name"
                                   type="text"
                                   autocomplete="name"
                                   required
                                   class="w-full rounded-2xl border border-slate-300 bg-white/85 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-100 dark:focus:border-indigo-400"
                                   placeholder="Введите ваше имя">
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Email</label>
                            <input id="email"
                                   name="email"
                                   type="email"
                                   autocomplete="email"
                                   required
                                   class="w-full rounded-2xl border border-slate-300 bg-white/85 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-100 dark:focus:border-indigo-400"
                                   placeholder="Введите email">
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Пароль</label>
                            <input id="password"
                                   name="password"
                                   type="password"
                                   autocomplete="new-password"
                                   required
                                   class="w-full rounded-2xl border border-slate-300 bg-white/85 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-100 dark:focus:border-indigo-400"
                                   placeholder="Создайте пароль">
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Минимум 8 символов.</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Подтверждение пароля</label>
                            <input id="password_confirmation"
                                   name="password_confirmation"
                                   type="password"
                                   required
                                   class="w-full rounded-2xl border border-slate-300 bg-white/85 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-100 dark:focus:border-indigo-400"
                                   placeholder="Повторите пароль">
                        </div>
                    </div>

                    <button type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-[0_18px_34px_-22px_rgba(79,70,229,0.72)] transition hover:bg-indigo-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Создать аккаунт
                    </button>

                    <p class="text-center text-sm text-slate-600 dark:text-slate-400">
                        Уже есть аккаунт?
                        <a href="/user/login" class="font-semibold text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-200">
                            Войти
                        </a>
                    </p>
                </form>
            </section>
        </div>
    </div>
</main>

<script>
    const registerParams = new URLSearchParams(window.location.search);

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

    document.getElementById('registerForm').addEventListener('submit', async function (event) {
        event.preventDefault();

        const password = document.getElementById('password').value;
        const passwordConfirmation = document.getElementById('password_confirmation').value;

        if (password !== passwordConfirmation) {
            showMessage('error', 'Пароли не совпадают.');
            return;
        }

        const formData = {
            name: document.getElementById('name').value.trim(),
            email: document.getElementById('email').value.trim().toLowerCase(),
            password,
            password_confirmation: passwordConfirmation
        };

        try {
            const response = await fetch('/api/register', {
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
                showMessage('success', data.message || 'Регистрация завершена.');

                setTimeout(() => {
                    const nextUrl = registerParams.get('next') || '/user/profile';
                    window.location.href = nextUrl;
                }, 900);
            } else if (data.errors) {
                const errorMessages = Object.values(data.errors).flat().join(', ');
                showMessage('error', errorMessages);
            } else {
                showMessage('error', data.message || 'Не удалось завершить регистрацию.');
            }
        } catch (error) {
            console.error('Register error:', error);
            showMessage('error', 'Ошибка соединения с сервером.');
        }
    });

    document.addEventListener('DOMContentLoaded', async () => {
        await redirectAuthenticatedUser('/user/profile');
    });
</script>
</body>
</html>
