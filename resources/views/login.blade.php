{{-- resources/views/user/login.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Вход'])
</head>
<body class="min-h-screen bg-gray-100 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <form id="loginForm" class="mt-8 space-y-6 rounded-xl border border-gray-200 bg-white p-8 shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <h2 class="text-3xl text-center font-bold text-gray-900 dark:text-white">Вход в аккаунт</h2>

            <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>
            <div id="successMessage" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded"></div>

            <div class="space-y-4">
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                           class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                           placeholder="Введите ваш email">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">Пароль</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                           class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                           placeholder="Введите пароль">
                </div>
            </div>

            <div class="flex justify-end">
                <div class="text-sm">
                    <a href="#" class="font-medium text-blue-500 hover:text-blue-600 transition duration-200">
                        Забыли пароль?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200">
                    Войти
                </button>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-slate-400">
                    Нет аккаунта?
                    <a href="/user/register" class="font-medium text-blue-500 hover:text-blue-600 transition duration-200">
                        Зарегистрироваться
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

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

    document.getElementById('loginForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = {
            email: document.getElementById('email').value.trim().toLowerCase(),
            password: document.getElementById('password').value
        };

        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (response.ok) {
                setAuthState(data);

                showMessage('success', data.message || 'Успешный вход!');

                setTimeout(() => {
                    const nextUrl = loginParams.get('next') || '/user/profile';
                    window.location.href = nextUrl;
                }, 1000);

            } else {
                if (data.errors) {
                    const errorMessages = Object.values(data.errors).flat().join(', ');
                    showMessage('error', errorMessages);
                } else {
                    showMessage('error', data.message || 'Ошибка входа');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('error', 'Ошибка соединения с сервером');
        }
    });

    document.addEventListener('DOMContentLoaded', async () => {
        const reason = loginParams.get('reason');
        if (reason === 'expired') {
            showMessage('error', 'Сессия истекла. Войдите снова или дождитесь автоматического обновления токена.');
        }

        await redirectAuthenticatedUser('/user/profile');
    });
</script>
</body>
</html>
