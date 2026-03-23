{{-- resources/views/tests/create.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание теста | BlanksProject</title>
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
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Создание нового теста</h1>
        <p class="text-gray-600 mt-2">Заполните информацию о тесте и добавьте вопросы</p>
    </div>

    <!-- Форма создания теста -->
    <form id="testForm" class="space-y-6">
        <!-- Основная информация -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Основная информация</h2>

            <div class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Название теста *</label>
                    <input type="text" id="title" name="title" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Введите название теста">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Описание</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Введите описание теста (необязательно)"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="time_limit" class="block text-sm font-medium text-gray-700 mb-2">Время выполнения (минут)</label>
                        <input type="number" id="time_limit" name="time_limit" min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Например: 60">
                    </div>

                    <div>
                        <label for="is_active" class="block text-sm font-medium text-gray-700 mb-2">Статус</label>
                        <select id="is_active" name="is_active"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="1">Активен</option>
                            <option value="0">Неактивен</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Вопросы -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Вопросы</h2>
                <button type="button" onclick="addQuestion()"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    Добавить вопрос
                </button>
            </div>

            <div id="questionsContainer" class="space-y-6">
                <!-- Вопросы будут добавляться сюда динамически -->
            </div>

            <div id="noQuestions" class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                <i class="fas fa-question-circle text-4xl mb-2"></i>
                <p>Нажмите "Добавить вопрос", чтобы начать</p>
            </div>
        </div>

        <!-- Кнопки -->
        <div class="flex justify-end space-x-4">
            <button type="button" onclick="window.location.href='/tests'"
                    class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                Отмена
            </button>
            <button type="submit"
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fas fa-save"></i>
                Сохранить тест
            </button>
        </div>
    </form>
</div>

<script>
    if (!localStorage.getItem('auth_token')) {
        window.location.href = '/user/login';
    }

    const token = localStorage.getItem('auth_token');

    // Добавление нового вопроса
    function addQuestion() {
        const container = document.getElementById('questionsContainer');
        const noQuestions = document.getElementById('noQuestions');

        if (noQuestions) {
            noQuestions.remove();
        }

        const questionId = 'new_' + Date.now() + '_' + Math.random();
        addQuestionToContainer(null, questionId);
    }

    // Добавление вопроса в контейнер
    function addQuestionToContainer(question = null, questionId = null) {
        const container = document.getElementById('questionsContainer');
        const newQuestionId = questionId || 'new_' + Date.now() + '_' + Math.random();
        const currentCount = container.children.length;

        const questionHtml = `
            <div class="question-item border border-gray-200 rounded-lg p-4" data-id="${newQuestionId}">
                <div class="flex justify-between items-start mb-3">
                    <h3 class="font-semibold text-gray-700">Вопрос ${currentCount + 1}</h3>
                    <button type="button" onclick="removeQuestion(this)" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Текст вопроса *</label>
                        <input type="text" class="question-text w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                               value="${question?.question_text || ''}" placeholder="Введите текст вопроса">
                        <div class="error-message hidden">Текст вопроса обязателен</div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Тип вопроса</label>
                            <select class="question-type w-full px-3 py-2 border border-gray-300 rounded-lg" onchange="toggleAnswersType(this, '${newQuestionId}')">
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
                            ${generateAnswersHtml(question?.answers || [{}, {}], question?.type || 'single', newQuestionId)}
                        </div>
                        <button type="button" onclick="addAnswer(this, '${newQuestionId}')"
                                class="mt-2 text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
                            <i class="fas fa-plus-circle"></i> Добавить вариант ответа
                        </button>
                        <div class="error-message answers-error hidden hidden">Добавьте варианты ответов</div>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', questionHtml);
    }

    // Генерация HTML для ответов
    function generateAnswersHtml(answers, type, questionId) {
        return answers.map((answer, index) => {
            return `
                <div class="answer-item flex items-center gap-2">
                    <input type="${type === 'single' ? 'radio' : 'checkbox'}"
                           class="answer-correct w-4 h-4"
                           name="correct_${questionId}"
                           ${answer.is_correct ? 'checked' : ''}>
                    <input type="text" class="answer-text flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           value="${answer.answer_text || ''}" placeholder="Вариант ответа ${index + 1}">
                    <button type="button" onclick="removeAnswer(this)" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }).join('');
    }

    // Переключение типа ответов
    function toggleAnswersType(select, questionId) {
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
    function addAnswer(button, questionId) {
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
                <button type="button" onclick="removeAnswer(this)" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        answersContainer.insertAdjacentHTML('beforeend', html);
    }

    // Удаление ответа
    function removeAnswer(button) {
        const answersContainer = button.closest('.answers-container');
        if (answersContainer.children.length > 2) {
            button.closest('.answer-item').remove();
        } else {
            alert('Должно быть минимум 2 варианта ответа');
        }
    }

    // Удаление вопроса
    function removeQuestion(button) {
        if (confirm('Удалить этот вопрос?')) {
            button.closest('.question-item').remove();
            // Обновляем нумерацию
            const questions = document.querySelectorAll('#questionsContainer .question-item');
            questions.forEach((q, index) => {
                q.querySelector('h3').textContent = `Вопрос ${index + 1}`;
            });

            // Если вопросов не осталось, показываем заглушку
            if (questions.length === 0) {
                const container = document.getElementById('questionsContainer');
                const noQuestionsHtml = `
                    <div id="noQuestions" class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-300 rounded-lg">
                        <i class="fas fa-question-circle text-4xl mb-2"></i>
                        <p>Нажмите "Добавить вопрос", чтобы начать</p>
                    </div>
                `;
                container.innerHTML = noQuestionsHtml;
            }
        }
    }

    // Валидация вопросов
    function validateQuestions() {
        let isValid = true;
        const questionItems = document.querySelectorAll('#questionsContainer .question-item');

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

    // Сохранение теста
    document.getElementById('testForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        // Валидация
        if (!validateQuestions()) {
            // Прокручиваем к первому вопросу с ошибкой
            const firstError = document.querySelector('.error-border');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        const questions = [];
        const questionItems = document.querySelectorAll('#questionsContainer .question-item');

        questionItems.forEach((item, index) => {
            const answers = [];
            const answerItems = item.querySelectorAll('.answer-item');

            answerItems.forEach((answerItem, aIndex) => {
                const text = answerItem.querySelector('.answer-text').value.trim();
                const isCorrect = answerItem.querySelector('.answer-correct').checked;

                if (text) {
                    answers.push({
                        answer_text: text,
                        is_correct: isCorrect,
                        order: aIndex
                    });
                }
            });

            const questionText = item.querySelector('.question-text').value.trim();

            if (questionText && answers.length >= 2) {
                questions.push({
                    question_text: questionText,
                    type: item.querySelector('.question-type').value,
                    points: parseInt(item.querySelector('.question-points').value) || 1,
                    order: index,
                    answers: answers
                });
            }
        });

        const testData = {
            title: document.getElementById('title').value.trim(),
            description: document.getElementById('description').value.trim() || null,
            time_limit: document.getElementById('time_limit').value ? parseInt(document.getElementById('time_limit').value) : null,
            is_active: document.getElementById('is_active').value === '1',
            questions: questions
        };

        if (!testData.title) {
            alert('Введите название теста');
            document.getElementById('title').focus();
            return;
        }

        console.log('Sending test data:', JSON.stringify(testData, null, 2));

        try {
            const response = await fetch('/api/tests', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(testData)
            });

            if (response.ok) {
                const data = await response.json();
                alert('Тест успешно создан!');
                window.location.href = `/tests/${data.data.id}`;
            } else {
                const error = await response.json();
                console.error('Create error:', error);
                alert('Ошибка при создании теста: ' + (error.message || 'Неизвестная ошибка'));
            }
        } catch (error) {
            console.error('Error creating test:', error);
            alert('Ошибка соединения с сервером');
        }
    });

    // Добавляем первый вопрос автоматически при загрузке
    document.addEventListener('DOMContentLoaded', () => {
        addQuestion();
    });
</script>
</body>
</html>
