{{-- resources/views/user/register.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Регистрация'])
</head>
<body class="min-h-screen bg-gray-100 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-lg w-full space-y-8">
        <form id="registerForm" class="mt-8 space-y-6 rounded-xl border border-gray-200 bg-white p-8 shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <h2 class="text-3xl text-center font-bold text-gray-900 dark:text-white">Создать аккаунт</h2>

            <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>
            <div id="successMessage" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded"></div>

            <div class="space-y-4">
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">Имя</label>
                    <input id="name" name="name" type="text" autocomplete="name" required
                           class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                           placeholder="Введите ваше имя">
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                           class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                           placeholder="Введите ваш email">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">Пароль</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                           class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                           placeholder="Создайте пароль">
                    <p class="mt-1 text-xs text-gray-500 dark:text-slate-400">Минимум 8 символов</p>
                </div>
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-300">Подтверждение пароля</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                           placeholder="Подтвердите пароль">
                </div>
            </div>

            <div>
                <button type="submit"
                        class="w-full py-3 px-4 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition duration-200">
                    Создать аккаунт
                </button>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-slate-400">
                    Уже есть аккаунт?
                    <a href="/user/login" class="font-medium text-blue-500 hover:text-blue-600 transition duration-200">
                        Войти
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

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

    document.getElementById('registerForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const password = document.getElementById('password').value;
        const passwordConfirmation = document.getElementById('password_confirmation').value;

        // Проверка совпадения паролей
        if (password !== passwordConfirmation) {
            showMessage('error', 'Пароли не совпадают');
            return;
        }

        const formData = {
            name: document.getElementById('name').value.trim(),
            email: document.getElementById('email').value.trim().toLowerCase(),
            password: password,
            password_confirmation: passwordConfirmation
        };

        try {
            const response = await fetch('/api/register', {
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

                showMessage('success', data.message || 'Регистрация успешна!');

                setTimeout(() => {
                    const nextUrl = registerParams.get('next') || '/user/profile';
                    window.location.href = nextUrl;
                }, 1000);
            } else {
                if (data.errors) {
                    const errorMessages = Object.values(data.errors).flat().join(', ');
                    showMessage('error', errorMessages);
                } else {
                    showMessage('error', data.message || 'Произошла ошибка');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('error', 'Ошибка соединения с сервером');
        }
    });

    document.addEventListener('DOMContentLoaded', async () => {
        await redirectAuthenticatedUser('/user/profile');
    });
</script>
</body>
</html>
