{{-- resources/views/tests/show.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр теста | BlanksProject</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .error-border {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        .error-message {
            color: #ef4444;
            font-size: 12px;
            margin-top: 4px;
        }
        .validation-error {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

@include('layouts.nav')

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <!-- Индикатор загрузки -->
    <div id="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
        <p class="text-gray-600 mt-4">Загрузка теста...</p>
    </div>

    <!-- Контент теста (режим просмотра) -->
    <div id="testContent" class="hidden">
        <!-- Шапка -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 id="testTitle" class="text-3xl font-bold text-gray-800 mb-2"></h1>
                    <p id="testDescription" class="text-gray-600 mb-4"></p>

                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span id="timeLimit" class="flex items-center gap-1">
                            <i class="far fa-clock"></i>
                        </span>
                        <span id="questionsCount" class="flex items-center gap-1">
                            <i class="far fa-question-circle"></i>
                        </span>
                        <span id="totalPoints" class="flex items-center gap-1">
                            <i class="fas fa-star"></i>
                        </span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button onclick="editTest()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <i class="fas fa-edit"></i>
                        Редактировать
                    </button>
                    <button onclick="printTest()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        Печать
                    </button>
                    <button onclick="window.location.href='/tests'" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                        Назад
                    </button>
                </div>
            </div>
        </div>

        <!-- Вопросы -->
        <div id="questionsList" class="space-y-4"></div>
    </div>

    <!-- Форма редактирования теста -->
    <div id="editForm" class="hidden">
        <form id="testEditForm" class="space-y-6">
            <!-- Основная информация -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Редактирование теста</h2>
                    <button type="button" onclick="cancelEdit()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="edit_title" class="block text-sm font-medium text-gray-700 mb-2">Название теста *</label>
                        <input type="text" id="edit_title" name="title" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-2">Описание</label>
                        <textarea id="edit_description" name="description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit_time_limit" class="block text-sm font-medium text-gray-700 mb-2">Время выполнения (минут)</label>
                            <input type="number" id="edit_time_limit" name="time_limit" min="1"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="edit_is_active" class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                            <select id="edit_is_active" name="is_active"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="1">Активен</option>
                                <option value="0">Неактивен</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Вопросы для редактирования -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Вопросы</h2>
                    <button type="button" onclick="addEditQuestion()"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        Добавить вопрос
                    </button>
                </div>

                <div id="editQuestionsContainer" class="space-y-6"></div>

                <div id="editNoQuestions" class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                    <i class="fas fa-question-circle text-4xl mb-2"></i>
                    <p>Добавьте вопросы к тесту</p>
                </div>
            </div>

            <!-- Кнопки сохранения -->
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="cancelEdit()"
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                    Отмена
                </button>
                <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    Сохранить изменения
                </button>
            </div>
        </form>
    </div>

    <!-- Ошибка -->
    <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"></div>
</div>

<script>
    if (!localStorage.getItem('auth_token')) {
        window.location.href = '/user/login';
    }

    const token = localStorage.getItem('auth_token');
    const testId = {{ $id }};
    let currentTest = null;

    // Загрузка теста
    async function loadTest() {
        try {
            const response = await fetch(`/api/tests/${testId}`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                currentTest = data.data || data;
                displayTest(currentTest);
            } else if (response.status === 401) {
                const refreshResult = await refreshToken();
                if (refreshResult) {
                    loadTest();
                } else {
                    window.location.href = '/user/login';
                }
            } else {
                showError('Ошибка загрузки теста');
            }
        } catch (error) {
            console.error('Error loading test:', error);
            showError('Ошибка соединения с сервером');
        }
    }

    // Отображение теста
    function displayTest(test) {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('testContent').classList.remove('hidden');
        document.getElementById('editForm').classList.add('hidden');

        document.getElementById('testTitle').textContent = test.title || 'Без названия';
        document.getElementById('testDescription').textContent = test.description || 'Нет описания';
        document.getElementById('timeLimit').innerHTML = `<i class="far fa-clock"></i> ${test.time_limit ? test.time_limit + ' мин' : 'Без ограничений'}`;
        document.getElementById('questionsCount').innerHTML = `<i class="far fa-question-circle"></i> ${test.questions?.length || 0} вопросов`;

        const totalPoints = test.questions ? test.questions.reduce((sum, q) => sum + (q.points || 1), 0) : 0;
        document.getElementById('totalPoints').innerHTML = `<i class="fas fa-star"></i> ${totalPoints} баллов`;

        const questionsList = document.getElementById('questionsList');

        if (!test.questions || test.questions.length === 0) {
            questionsList.innerHTML = `
                <div class="bg-white rounded-lg shadow-lg p-6 text-center text-gray-500">
                    <i class="fas fa-question-circle text-4xl mb-2"></i>
                    <p>В этом тесте пока нет вопросов</p>
                </div>
            `;
            return;
        }

        questionsList.innerHTML = test.questions.map((question, qIndex) => {
            const answers = question.answers || [];
            return `
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            ${qIndex + 1}. ${escapeHtml(question.question_text)}
                        </h3>
                        <span class="px-2 py-1 text-xs rounded-full ${question.type === 'single' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'}">
                            ${question.type === 'single' ? 'Один вариант' : 'Несколько вариантов'} | ${question.points || 1} балл(ов)
                        </span>
                    </div>

                    <div class="space-y-2 ml-4">
                        ${answers.map((answer, aIndex) => `
                            <div class="flex items-center gap-2 ${answer.is_correct ? 'text-green-600 font-medium' : 'text-gray-700'}">
                                ${question.type === 'single'
                ? '<i class="far fa-circle text-xs"></i>'
                : '<i class="far fa-square text-xs"></i>'}
                                <span>${String.fromCharCode(65 + aIndex)}. ${escapeHtml(answer.answer_text)}</span>
                                ${answer.is_correct ? '<i class="fas fa-check text-green-600 ml-2"></i>' : ''}
                            </div>
                        `).join('')}
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

    // Переход в режим редактирования
    function editTest() {
        document.getElementById('testContent').classList.add('hidden');
        document.getElementById('editForm').classList.remove('hidden');

        // Заполняем основную информацию
        document.getElementById('edit_title').value = currentTest.title || '';
        document.getElementById('edit_description').value = currentTest.description || '';
        document.getElementById('edit_time_limit').value = currentTest.time_limit || '';
        document.getElementById('edit_is_active').value = currentTest.is_active ? '1' : '0';

        // Отображаем вопросы для редактирования
        displayEditQuestions(currentTest.questions || []);
    }

    // Отмена редактирования
    function cancelEdit() {
        displayTest(currentTest);
    }

    // Отображение вопросов в режиме редактирования
    function displayEditQuestions(questions) {
        const container = document.getElementById('editQuestionsContainer');
        const noQuestions = document.getElementById('editNoQuestions');

        container.innerHTML = '';

        if (questions.length === 0) {
            noQuestions.classList.remove('hidden');
            return;
        }

        noQuestions.classList.add('hidden');

        questions.forEach((question, index) => {
            addEditQuestionToContainer(question, index);
        });
    }

    // Добавление нового вопроса в форму редактирования
    function addEditQuestion() {
        const container = document.getElementById('editQuestionsContainer');
        const noQuestions = document.getElementById('editNoQuestions');

        if (noQuestions) {
            noQuestions.classList.add('hidden');
        }

        addEditQuestionToContainer(null, container.children.length);
    }

    // Добавление вопроса в контейнер
    function addEditQuestionToContainer(question = null, index = null) {
        const container = document.getElementById('editQuestionsContainer');
        const questionId = question?.id || 'new_' + Date.now() + '_' + Math.random();
        const currentCount = container.children.length;

        const questionHtml = `
            <div class="question-item border border-gray-200 rounded-lg p-4" data-id="${questionId}">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="font-semibold text-gray-700">Вопрос ${currentCount + 1}</h3>
                    <button type="button" onclick="removeEditQuestion(this)" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Текст вопроса *</label>
                        <input type="text" class="question-text w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                               value="${escapeHtml(question?.question_text || '')}" placeholder="Введите текст вопроса">
                        <div class="error-message hidden">Текст вопроса обязателен</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Тип вопроса</label>
                            <select class="question-type w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="toggleEditAnswersType(this, '${questionId}')">
                                <option value="single" ${question?.type === 'single' ? 'selected' : ''}>Один вариант</option>
                                <option value="multiple" ${question?.type === 'multiple' ? 'selected' : ''}>Несколько вариантов</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Баллы *</label>
                            <input type="number" class="question-points w-full px-3 py-2 border border-gray-300 rounded-lg" value="${question?.points || 1}" min="1" step="1">
                            <div class="error-message hidden">Баллы должны быть от 1 и выше</div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Варианты ответов *</label>
                        <div class="answers-container space-y-2">
                            ${generateEditAnswersHtml(question?.answers || [{}, {}], question?.type || 'single', questionId)}
                        </div>
                        <button type="button" onclick="addEditAnswer(this, '${questionId}')"
                                class="mt-2 text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
                            <i class="fas fa-plus-circle"></i> Добавить вариант ответа
                        </button>
                        <div class="error-message answers-error hidden"></div>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', questionHtml);
    }

    // Генерация HTML для ответов
    function generateEditAnswersHtml(answers, type, questionId) {
        return answers.map((answer, index) => {
            const answerId = answer.id ? `data-id="${answer.id}"` : '';
            return `
                <div class="answer-item flex items-center gap-2" ${answerId}>
                    <input type="${type === 'single' ? 'radio' : 'checkbox'}"
                           class="answer-correct w-4 h-4"
                           name="correct_${questionId}"
                           ${answer.is_correct ? 'checked' : ''}>
                    <input type="text" class="answer-text flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           value="${escapeHtml(answer.answer_text || '')}" placeholder="Вариант ответа ${index + 1}">
                    <button type="button" onclick="removeEditAnswer(this)" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }).join('');
    }

    // Переключение типа ответов
    function toggleEditAnswersType(select, questionId) {
        const questionItem = select.closest('.question-item');
        const answersContainer = questionItem.querySelector('.answers-container');
        const answerItems = answersContainer.querySelectorAll('.answer-item');
        const type = select.value;

        answerItems.forEach((item, index) => {
            const checkbox = item.querySelector('.answer-correct');
            checkbox.type = type === 'single' ? 'radio' : 'checkbox';
            checkbox.name = type === 'single' ? `correct_${questionId}` : '';
            if (type === 'multiple') checkbox.checked = false;
        });
    }

    // Добавление нового ответа
    function addEditAnswer(button, questionId) {
        const answersContainer = button.closest('.question-item').querySelector('.answers-container');
        const type = button.closest('.question-item').querySelector('.question-type').value;
        const answerCount = answersContainer.children.length + 1;

        const html = `
            <div class="answer-item flex items-center gap-2">
                <input type="${type === 'single' ? 'radio' : 'checkbox'}"
                       class="answer-correct w-4 h-4"
                       name="${type === 'single' ? `correct_${questionId}` : ''}">
                <input type="text" class="answer-text flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                       placeholder="Вариант ответа ${answerCount}">
                <button type="button" onclick="removeEditAnswer(this)" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        answersContainer.insertAdjacentHTML('beforeend', html);
    }

    // Удаление ответа
    function removeEditAnswer(button) {
        const answersContainer = button.closest('.answers-container');
        if (answersContainer.children.length > 2) {
            button.closest('.answer-item').remove();
        } else {
            alert('Должно быть минимум 2 варианта ответа');
        }
    }

    // Удаление вопроса
    function removeEditQuestion(button) {
        if (confirm('Удалить этот вопрос?')) {
            button.closest('.question-item').remove();

            // Обновляем нумерацию
            const questions = document.querySelectorAll('#editQuestionsContainer .question-item');
            questions.forEach((q, index) => {
                q.querySelector('h3').textContent = `Вопрос ${index + 1}`;
            });

            // Если вопросов не осталось, показываем заглушку
            if (questions.length === 0) {
                document.getElementById('editNoQuestions').classList.remove('hidden');
            }
        }
    }

    // Валидация вопросов
    function validateEditQuestions() {
        let isValid = true;
        const questionItems = document.querySelectorAll('#editQuestionsContainer .question-item');

        if (questionItems.length === 0) {
            alert('Добавьте хотя бы один вопрос');
            return false;
        }

        questionItems.forEach((item, index) => {
            // Проверка текста вопроса
            const questionText = item.querySelector('.question-text').value.trim();
            const textError = item.querySelector('.question-text').closest('div').querySelector('.error-message');

            if (!questionText) {
                item.querySelector('.question-text').classList.add('error-border');
                if (textError) textError.classList.remove('hidden');
                isValid = false;
            } else {
                item.querySelector('.question-text').classList.remove('error-border');
                if (textError) textError.classList.add('hidden');
            }

            // Проверка баллов
            const points = parseInt(item.querySelector('.question-points').value);
            const pointsError = item.querySelector('.question-points').closest('div').querySelector('.error-message');

            if (!points || points < 1) {
                item.querySelector('.question-points').classList.add('error-border');
                if (pointsError) pointsError.classList.remove('hidden');
                isValid = false;
            } else {
                item.querySelector('.question-points').classList.remove('error-border');
                if (pointsError) pointsError.classList.add('hidden');
            }

            // Проверка ответов
            const answerItems = item.querySelectorAll('.answer-item');
            const answersError = item.querySelector('.answers-error');
            let hasEmptyAnswer = false;
            let hasCorrectAnswer = false;
            let validAnswersCount = 0;

            answerItems.forEach(answerItem => {
                const answerText = answerItem.querySelector('.answer-text').value.trim();
                const isCorrect = answerItem.querySelector('.answer-correct').checked;

                if (answerText) {
                    validAnswersCount++;
                    if (isCorrect) hasCorrectAnswer = true;
                } else {
                    hasEmptyAnswer = true;
                    answerItem.querySelector('.answer-text').classList.add('error-border');
                }
            });

            // Проверка на пустые ответы
            if (hasEmptyAnswer) {
                if (answersError) {
                    answersError.textContent = 'Заполните все варианты ответов или удалите пустые';
                    answersError.classList.remove('hidden');
                }
                isValid = false;
            }
            // Проверка на количество ответов
            else if (validAnswersCount < 2) {
                if (answersError) {
                    answersError.textContent = 'Должно быть минимум 2 варианта ответа';
                    answersError.classList.remove('hidden');
                }
                isValid = false;
            }
            // Проверка на наличие правильного ответа
            else if (!hasCorrectAnswer) {
                if (answersError) {
                    answersError.textContent = 'Выберите хотя бы один правильный вариант ответа';
                    answersError.classList.remove('hidden');
                }
                isValid = false;
            }
            else {
                if (answersError) answersError.classList.add('hidden');
                // Убираем error-border со всех полей ответов
                answerItems.forEach(answerItem => {
                    answerItem.querySelector('.answer-text').classList.remove('error-border');
                });
            }
        });

        return isValid;
    }

    // Сохранение изменений
    document.getElementById('testEditForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        // Валидация
        if (!validateEditQuestions()) {
            // Прокручиваем к первому вопросу с ошибкой
            const firstError = document.querySelector('.error-border');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        const questions = [];
        const questionItems = document.querySelectorAll('#editQuestionsContainer .question-item');

        questionItems.forEach((item, index) => {
            const answers = [];
            const answerItems = item.querySelectorAll('.answer-item');

            answerItems.forEach((answerItem, aIndex) => {
                const text = answerItem.querySelector('.answer-text').value.trim();
                const isCorrect = answerItem.querySelector('.answer-correct').checked;
                const answerId = answerItem.dataset.id;

                if (text) {
                    const answerData = {
                        answer_text: text,
                        is_correct: isCorrect,
                        order: aIndex
                    };

                    if (answerId && !answerId.toString().startsWith('new_')) {
                        answerData.id = parseInt(answerId);
                    }

                    answers.push(answerData);
                }
            });

            const questionText = item.querySelector('.question-text').value.trim();
            const questionId = item.dataset.id;

            if (questionText && answers.length >= 2) {
                const questionData = {
                    question_text: questionText,
                    type: item.querySelector('.question-type').value,
                    points: parseInt(item.querySelector('.question-points').value) || 1,
                    order: index,
                    answers: answers
                };

                if (questionId && !questionId.toString().startsWith('new_')) {
                    questionData.id = parseInt(questionId);
                }

                questions.push(questionData);
            }
        });

        if (questions.length === 0) {
            alert('Добавьте хотя бы один вопрос с вариантами ответов');
            return;
        }

        const testData = {
            title: document.getElementById('edit_title').value.trim(),
            description: document.getElementById('edit_description').value.trim() || null,
            time_limit: document.getElementById('edit_time_limit').value ? parseInt(document.getElementById('edit_time_limit').value) : null,
            is_active: document.getElementById('edit_is_active').value === '1',
            questions: questions
        };

        if (!testData.title) {
            alert('Введите название теста');
            document.getElementById('edit_title').focus();
            return;
        }

        console.log('Sending test data:', JSON.stringify(testData, null, 2));

        try {
            const response = await fetch(`/api/tests/${testId}`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(testData)
            });

            if (response.ok) {
                const data = await response.json();
                alert('Тест успешно обновлен!');
                currentTest = data.data || data;
                displayTest(currentTest);
            } else {
                const error = await response.json();
                console.error('Update error:', error);
                alert('Ошибка при обновлении теста: ' + (error.message || 'Неизвестная ошибка'));
            }
        } catch (error) {
            console.error('Error updating test:', error);
            alert('Ошибка соединения с сервером');
        }
    });

    // Обновление токена
    async function refreshToken() {
        try {
            const response = await fetch('/api/refresh', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();
                const newToken = data.authorization?.token || data.token;
                if (newToken) {
                    localStorage.setItem('auth_token', newToken);
                }
                return true;
            }
            return false;
        } catch (error) {
            console.error('Refresh error:', error);
            return false;
        }
    }

    // Печать теста
    function printTest() {
        window.location.href = `/tests/${testId}/print`;
    }

    // Показать ошибку
    function showError(message) {
        document.getElementById('loading').classList.add('hidden');
        const errorMsg = document.getElementById('errorMessage');
        errorMsg.textContent = message;
        errorMsg.classList.remove('hidden');

        setTimeout(() => {
            errorMsg.classList.add('hidden');
        }, 5000);
    }

    // Загружаем тест при загрузке страницы
    document.addEventListener('DOMContentLoaded', loadTest);
</script>
</body>
</html>
