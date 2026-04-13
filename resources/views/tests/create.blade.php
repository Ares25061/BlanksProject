<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание теста | BlanksProject</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .error-border {
            border-color: #ef4444 !important;
            background-color: #fff1f2 !important;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">

@include('layouts.nav')

<div class="container mx-auto px-4 py-8 max-w-5xl">
    <div class="mb-8">
        <p class="text-sm uppercase tracking-[0.3em] text-sky-700 font-semibold">Новый тест</p>
        <h1 class="text-3xl font-bold mt-2">Создание теста</h1>
        <p class="text-slate-600 mt-2">Соберите вопросы, укажите предмет, баллы и шкалу оценивания, по которой будут считаться результаты сканирования.</p>
    </div>

    <form id="testForm" class="space-y-6">
        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="flex flex-wrap justify-between gap-4 items-center">
                <h2 class="text-xl font-semibold">Основная информация</h2>
                <div class="text-sm text-slate-500">
                    Формат бланка: неограниченное число вопросов и до <span class="font-semibold text-slate-700">4 вариантов ответа</span> на вопрос
                </div>
            </div>

            <div>
                <label for="subject_name" class="block text-sm font-medium text-slate-700 mb-2">Предмет</label>
                <input id="subject_name" type="text" required
                       class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                       placeholder="Например: Информатика">
                <p class="text-xs text-slate-500 mt-2">Один и тот же класс можно будет вести по нескольким предметам через отдельные журналы.</p>
            </div>

            <div>
                <label for="title" class="block text-sm font-medium text-slate-700 mb-2">Название теста</label>
                <input id="title" type="text" required
                       class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                       placeholder="Например: Контрольная работа №3">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-slate-700 mb-2">Описание</label>
                <textarea id="description" rows="3"
                          class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                          placeholder="Кратко опишите тему, формат или особенности проведения"></textarea>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label for="time_limit" class="block text-sm font-medium text-slate-700 mb-2">Время выполнения</label>
                    <input id="time_limit" type="number" min="1"
                           class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                           placeholder="Минуты">
                </div>

                <div>
                    <label for="variant_count" class="block text-sm font-medium text-slate-700 mb-2">Количество вариантов</label>
                    <input id="variant_count" type="number" min="1" max="10" value="1"
                           class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                           placeholder="От 1 до 10">
                    <p class="text-xs text-slate-500 mt-2">До 10 вариантов. На печати можно будет выдать всем один вариант, распределить поровну или назначить вручную.</p>
                </div>

                <div>
                    <label for="is_active" class="block text-sm font-medium text-slate-700 mb-2">Статус</label>
                    <select id="is_active" class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="1">Активен</option>
                        <option value="0">Черновик</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="flex flex-wrap justify-between items-start gap-4">
                <div>
                    <h2 class="text-xl font-semibold">Импорт вопросов</h2>
                    <p class="text-slate-500 mt-1">Можно загрузить готовый список вопросов из `JSON` или `XLSX` и дальше только подправить его вручную.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <input id="questionsImportInput" type="file" accept=".json,.xlsx" class="hidden">
                    <button type="button" onclick="document.getElementById('questionsImportInput').click()" class="bg-slate-900 text-white px-4 py-2 rounded-xl hover:bg-slate-800 transition flex items-center gap-2">
                        <i class="fas fa-file-import"></i>
                        Импортировать файл
                    </button>
                </div>
            </div>

            <div class="grid lg:grid-cols-[1.15fr_0.85fr] gap-4">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <div class="font-semibold text-slate-900 mb-2">Поддерживаемый JSON</div>
                    <div>Можно загрузить либо старый формат без вариантов, либо новый формат с полем <code>variant</code> у каждого вопроса. Если поле <code>variant</code> не указано, вопрос попадет в вариант <code>1</code>.</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <div class="font-semibold text-slate-900 mb-2">Поддерживаемый XLSX</div>
                    <div>Первая строка должна быть заголовком. Колонки: <code>question_text</code>, <code>variant</code>, <code>type</code>, <code>points</code>, <code>answer_a</code> ... <code>answer_d</code>, <code>correct</code>.</div>
                    <div class="mt-2">В колонке <code>correct</code> можно писать, например, <code>A</code>, <code>B,D</code> или <code>1 3</code>.</div>
                </div>
            </div>

            <div id="importStatus" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"></div>
        </section>

        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                <div>
                    <h2 class="text-xl font-semibold">Критерии оценивания</h2>
                    <p class="text-slate-500 mt-1">Шкала используется при автопроверке после загрузки сканов.</p>
                </div>
                <button type="button" onclick="fillSuggestedCriteria()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl transition">
                    Подставить рекомендуемые пороги
                </button>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4" id="gradeCriteriaContainer"></div>

            <button type="button" onclick="addGradeCriterion()" class="text-sky-600 hover:text-sky-800 text-sm font-medium flex items-center gap-2">
                <i class="fas fa-plus-circle"></i>
                Добавить уровень оценки
            </button>

                <div class="mt-4 bg-sky-50 border border-sky-100 rounded-2xl p-4 text-sm text-sky-900">
                    <span id="totalPointsLabel">Текущий максимальный балл по вопросам:</span>
                    <span id="totalPointsSummary" class="font-semibold">0</span>
                </div>
            </section>

        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                <div>
                    <h2 class="text-xl font-semibold">Вопросы</h2>
                    <p class="text-slate-500 mt-1">Укажите текст, тип и баллы за каждый вопрос. Если вопросов будет много, бланк ответов сам перенесется на следующие листы.</p>
                </div>
                <button type="button" onclick="addQuestion()" class="bg-emerald-600 text-white px-4 py-2 rounded-xl hover:bg-emerald-500 transition flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    Добавить вопрос
                </button>
            </div>

            <div id="questionsContainer" class="space-y-5"></div>

            <div id="noQuestions" class="text-center py-10 border-2 border-dashed border-slate-300 rounded-2xl text-slate-500">
                Вопросов пока нет. Добавьте хотя бы один вопрос.
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="button" onclick="window.location.href='/tests'" class="px-6 py-3 rounded-2xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                Отмена
            </button>
            <button type="submit" class="px-6 py-3 rounded-2xl bg-sky-600 text-white hover:bg-sky-500 transition shadow-sm flex items-center gap-2">
                <i class="fas fa-save"></i>
                Сохранить тест
            </button>
        </div>
    </form>
</div>

<template id="questionTemplate">
    <article class="question-item border border-slate-200 rounded-3xl p-5 bg-slate-50" data-id="">
        <div class="flex justify-between items-start gap-3 mb-4">
            <h3 class="text-lg font-semibold text-slate-800 question-title"></h3>
            <button type="button" onclick="removeQuestion(this)" class="text-rose-600 hover:text-rose-800">
                <i class="fas fa-trash"></i>
            </button>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Текст вопроса</label>
                <input type="text" class="question-text w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                       placeholder="Введите текст вопроса">
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Тип вопроса</label>
                    <select class="question-type w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="single">Один правильный ответ</option>
                        <option value="multiple">Несколько правильных ответов</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Баллы</label>
                    <input type="number" class="question-points w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500" min="1" value="1">
                </div>

                <div class="question-variant-wrapper hidden">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Вариант</label>
                    <select class="question-variant w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></select>
                    <p class="question-variant-hint text-xs text-slate-500 mt-2">Этот вопрос попадет в выбранный вариант теста.</p>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center gap-3 mb-2">
                    <label class="block text-sm font-medium text-slate-700">Варианты ответов</label>
                    <button type="button" class="text-sky-600 hover:text-sky-800 text-sm font-medium add-answer-button">
                        <i class="fas fa-plus-circle"></i>
                        Добавить ответ
                    </button>
                </div>
                <div class="answers-container space-y-2"></div>
            </div>
        </div>
    </article>
</template>

<template id="answerTemplate">
    <div class="answer-item flex items-center gap-3">
        <input type="radio" class="answer-correct w-4 h-4 text-sky-600 border-slate-300">
        <input type="text" class="answer-text flex-1 px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
               placeholder="Вариант ответа">
        <button type="button" onclick="removeAnswer(this)" class="text-rose-600 hover:text-rose-800">
            <i class="fas fa-times"></i>
        </button>
    </div>
</template>

<template id="gradeCriterionTemplate">
    <div class="grade-criterion bg-slate-50 border border-slate-200 rounded-2xl p-4">
        <div class="flex justify-between items-center gap-3 mb-3">
            <label class="text-sm font-medium text-slate-700">Оценка</label>
            <button type="button" onclick="removeGradeCriterion(this)" class="text-rose-500 hover:text-rose-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <input type="text" class="criterion-label w-full px-3 py-2 rounded-xl border border-slate-300 mb-3 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
               placeholder="Например: 5 (Отлично)">
        <input type="number" min="0" class="criterion-min-points w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
               placeholder="Минимум баллов">
    </div>
</template>

<script>
    const MAX_ANSWERS = 4;
    const DEFAULT_VARIANT_COUNT = 1;

    function createUid() {
        return `q_${Date.now()}_${Math.random().toString(16).slice(2)}`;
    }

    function normalizeVariantCountValue(value = null) {
        const source = value ?? document.getElementById('variant_count').value;
        const parsed = parseInt(source, 10) || DEFAULT_VARIANT_COUNT;

        return Math.max(1, Math.min(10, parsed));
    }

    function getAvailableVariantNumbers() {
        return Array.from({ length: normalizeVariantCountValue() }, (_, index) => index + 1);
    }

    function renderQuestionVariantOptions(questionItem, selectedVariant = 1) {
        const wrapper = questionItem.querySelector('.question-variant-wrapper');
        const select = questionItem.querySelector('.question-variant');
        const variantCount = normalizeVariantCountValue();
        const normalizedSelectedVariant = Math.max(1, Math.min(variantCount, parseInt(selectedVariant, 10) || 1));

        select.innerHTML = getAvailableVariantNumbers().map((variantNumber) => `
            <option value="${variantNumber}" ${variantNumber === normalizedSelectedVariant ? 'selected' : ''}>
                Вариант ${variantNumber}
            </option>
        `).join('');

        wrapper.classList.toggle('hidden', variantCount <= 1);
    }

    function refreshQuestionVariantOptions() {
        document.getElementById('variant_count').value = String(normalizeVariantCountValue());

        document.querySelectorAll('.question-item').forEach((questionItem) => {
            renderQuestionVariantOptions(
                questionItem,
                questionItem.querySelector('.question-variant')?.value || 1
            );
        });

        updateTotalPoints();
    }

    function addQuestion(question = null) {
        const container = document.getElementById('questionsContainer');
        const noQuestions = document.getElementById('noQuestions');

        noQuestions.classList.add('hidden');

        const template = document.getElementById('questionTemplate');
        const fragment = template.content.cloneNode(true);
        const article = fragment.querySelector('.question-item');
        const questionId = question?.id || createUid();
        article.dataset.id = questionId;
        article.querySelector('.question-title').textContent = `Вопрос ${container.children.length + 1}`;
        article.querySelector('.question-text').value = question?.question_text || '';
        article.querySelector('.question-type').value = question?.type || 'single';
        article.querySelector('.question-points').value = question?.points || 1;

        const addAnswerButton = article.querySelector('.add-answer-button');
        addAnswerButton.addEventListener('click', () => addAnswer(addAnswerButton, questionId));

        container.appendChild(fragment);

        const insertedQuestion = container.lastElementChild;
        renderQuestionVariantOptions(insertedQuestion, question?.variant_number ?? question?.variant ?? 1);
        const answers = question?.answers || [{}, {}];
        answers.forEach((answer) => addAnswer(insertedQuestion.querySelector('.add-answer-button'), questionId, answer));

        insertedQuestion.querySelector('.question-type').addEventListener('change', (event) => {
            updateAnswerSelectors(insertedQuestion, event.target.value, questionId);
        });

        updateAnswerSelectors(insertedQuestion, insertedQuestion.querySelector('.question-type').value, questionId);
        updateQuestionsNumbering();
        updateTotalPoints();
    }

    function addAnswer(button, questionId, answer = null) {
        const questionItem = button.closest('.question-item');
        const answersContainer = questionItem.querySelector('.answers-container');
        if (!answer && answersContainer.children.length >= MAX_ANSWERS) {
            alert(`Для одного вопроса доступно не более ${MAX_ANSWERS} вариантов ответа`);
            return;
        }
        const template = document.getElementById('answerTemplate');
        const fragment = template.content.cloneNode(true);
        const answerItem = fragment.querySelector('.answer-item');
        const type = questionItem.querySelector('.question-type').value;

        answerItem.dataset.id = answer?.id || '';
        answerItem.querySelector('.answer-correct').type = type === 'single' ? 'radio' : 'checkbox';
        answerItem.querySelector('.answer-correct').name = `correct_${questionId}`;
        answerItem.querySelector('.answer-correct').checked = Boolean(answer?.is_correct);
        answerItem.querySelector('.answer-text').value = answer?.answer_text || '';

        answersContainer.appendChild(fragment);
    }

    function removeAnswer(button) {
        const answersContainer = button.closest('.answers-container');
        if (answersContainer.children.length <= 2) {
            alert('У вопроса должно остаться минимум 2 варианта ответа');
            return;
        }
        button.closest('.answer-item').remove();
    }

    function updateAnswerSelectors(questionItem, type, questionId) {
        questionItem.querySelectorAll('.answer-correct').forEach((input) => {
            input.type = type === 'single' ? 'radio' : 'checkbox';
            input.name = `correct_${questionId}`;
        });
    }

    function removeQuestion(button) {
        button.closest('.question-item').remove();
        updateQuestionsNumbering();
        updateTotalPoints();
        if (!document.querySelectorAll('.question-item').length) {
            document.getElementById('noQuestions').classList.remove('hidden');
        }
    }

    function updateQuestionsNumbering() {
        document.querySelectorAll('.question-item').forEach((item, index) => {
            item.querySelector('.question-title').textContent = `Вопрос ${index + 1}`;
        });
    }

    function addGradeCriterion(criterion = null) {
        const template = document.getElementById('gradeCriterionTemplate');
        const fragment = template.content.cloneNode(true);
        const item = fragment.querySelector('.grade-criterion');
        item.querySelector('.criterion-label').value = criterion?.label || '';
        item.querySelector('.criterion-min-points').value = criterion?.min_points ?? '';
        document.getElementById('gradeCriteriaContainer').appendChild(fragment);
    }

    function removeGradeCriterion(button) {
        if (document.querySelectorAll('.grade-criterion').length <= 1) {
            alert('Должен остаться хотя бы один критерий');
            return;
        }
        button.closest('.grade-criterion').remove();
    }

    function getTotalPoints() {
        return Array.from(document.querySelectorAll('.question-points'))
            .map((input) => parseInt(input.value, 10) || 0)
            .reduce((sum, value) => sum + value, 0);
    }

    function getVariantScoreTotals() {
        const variantCount = normalizeVariantCountValue();
        const totals = Array.from({ length: variantCount }, () => 0);

        document.querySelectorAll('.question-item').forEach((questionItem) => {
            const questionVariant = Math.max(1, Math.min(
                variantCount,
                parseInt(questionItem.querySelector('.question-variant')?.value, 10) || 1
            ));
            const points = parseInt(questionItem.querySelector('.question-points')?.value, 10) || 0;
            totals[questionVariant - 1] += points;
        });

        return totals;
    }

    function getReferenceTotalPoints() {
        const totals = getVariantScoreTotals().filter((value) => value > 0);

        return totals.length ? Math.min(...totals) : 0;
    }

    function updateTotalPoints() {
        const variantCount = normalizeVariantCountValue();
        const label = document.getElementById('totalPointsLabel');
        const summary = document.getElementById('totalPointsSummary');

        if (variantCount <= 1) {
            label.textContent = 'Текущий максимальный балл по вопросам:';
            summary.textContent = String(getTotalPoints());
            return;
        }

        label.textContent = 'Баллы по вариантам:';
        summary.textContent = getVariantScoreTotals()
            .map((value, index) => `В${index + 1}: ${value}`)
            .join(' • ');
    }

    function fillSuggestedCriteria() {
        const total = getReferenceTotalPoints();
        const defaults = [
            { label: '5 (Отлично)', min_points: total ? Math.ceil(total * 0.9) : 0 },
            { label: '4 (Хорошо)', min_points: total ? Math.ceil(total * 0.75) : 0 },
            { label: '3 (Удовлетворительно)', min_points: total ? Math.ceil(total * 0.6) : 0 },
            { label: '2 (Нужно доработать)', min_points: 0 }
        ];

        const container = document.getElementById('gradeCriteriaContainer');
        container.innerHTML = '';
        defaults.forEach(addGradeCriterion);
    }

    async function importQuestionsFromFile(file) {
        if (!file) {
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        const response = await authApiFetch('/api/tests/import-questions', {
            method: 'POST',
            headers: {
                'Accept': 'application/json'
            },
            body: formData
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(payload.message || payload.errors?.file?.[0] || 'Не удалось импортировать файл');
        }

        applyImportedTestData(payload.data || {});
    }

    function applyImportedTestData(imported) {
        const importedQuestions = imported.questions || [];
        if (!importedQuestions.length) {
            throw new Error('В файле не найдено ни одного вопроса');
        }

        const existingQuestions = document.querySelectorAll('.question-item').length;
        const shouldReplace = !existingQuestions
            || confirm('Заменить текущие вопросы импортируемыми? Нажмите "Отмена", чтобы добавить новые вопросы в конец.');

        if (shouldReplace) {
            document.getElementById('questionsContainer').innerHTML = '';
            document.getElementById('noQuestions').classList.add('hidden');
        }

        importedQuestions.forEach((question) => addQuestion(question));
        maybeApplyImportedMetadata(imported);
        renderImportStatus(importedQuestions.length, shouldReplace);
    }

    function maybeApplyImportedMetadata(imported) {
        if (!document.getElementById('subject_name').value.trim() && imported.subject_name) {
            document.getElementById('subject_name').value = imported.subject_name;
        }

        if (!document.getElementById('title').value.trim() && imported.title) {
            document.getElementById('title').value = imported.title;
        }

        if (!document.getElementById('description').value.trim() && imported.description) {
            document.getElementById('description').value = imported.description;
        }

        if (!document.getElementById('time_limit').value && imported.time_limit) {
            document.getElementById('time_limit').value = imported.time_limit;
        }

        if (imported.variant_count) {
            document.getElementById('variant_count').value = imported.variant_count;
            refreshQuestionVariantOptions();
        }

        if ((imported.grade_criteria || []).length && confirm('В файле найдены критерии оценивания. Заменить текущую шкалу?')) {
            document.getElementById('gradeCriteriaContainer').innerHTML = '';
            imported.grade_criteria.forEach(addGradeCriterion);
        }
    }

    function renderImportStatus(questionCount, replaced) {
        const status = document.getElementById('importStatus');
        status.classList.remove('hidden');
        status.textContent = replaced
            ? `Импортировано вопросов: ${questionCount}. Текущий список полностью заменен содержимым файла.`
            : `Импортировано вопросов: ${questionCount}. Они добавлены в конец текущего списка.`;
    }

    function collectGradeCriteria() {
        return Array.from(document.querySelectorAll('.grade-criterion')).map((criterion) => ({
            label: criterion.querySelector('.criterion-label').value.trim(),
            min_points: parseInt(criterion.querySelector('.criterion-min-points').value, 10) || 0
        }));
    }

    function validateForm() {
        let valid = true;
        document.querySelectorAll('.error-border').forEach((item) => item.classList.remove('error-border'));

        const title = document.getElementById('title');
        const subjectName = document.getElementById('subject_name');
        const variantCount = document.getElementById('variant_count');
        if (!subjectName.value.trim()) {
            subjectName.classList.add('error-border');
            valid = false;
        }

        if (!title.value.trim()) {
            title.classList.add('error-border');
            valid = false;
        }

        const normalizedVariantCount = parseInt(variantCount.value, 10) || 1;
        if (normalizedVariantCount < 1 || normalizedVariantCount > 10) {
            variantCount.classList.add('error-border');
            valid = false;
        }

        const questions = document.querySelectorAll('.question-item');
        if (!questions.length) {
            alert('Добавьте хотя бы один вопрос');
            return false;
        }

        questions.forEach((question) => {
            const questionText = question.querySelector('.question-text');
            const points = question.querySelector('.question-points');
            const questionVariant = question.querySelector('.question-variant');
            const answers = question.querySelectorAll('.answer-item');
            const filledAnswers = [];
            let hasCorrect = false;

            if (!questionText.value.trim()) {
                questionText.classList.add('error-border');
                valid = false;
            }

            if ((parseInt(points.value, 10) || 0) < 1) {
                points.classList.add('error-border');
                valid = false;
            }

            if ((parseInt(questionVariant?.value, 10) || 1) < 1 || (parseInt(questionVariant?.value, 10) || 1) > normalizedVariantCount) {
                questionVariant?.classList.add('error-border');
                valid = false;
            }

            answers.forEach((answer) => {
                const answerText = answer.querySelector('.answer-text');
                const isCorrect = answer.querySelector('.answer-correct').checked;

                if (!answerText.value.trim()) {
                    answerText.classList.add('error-border');
                    valid = false;
                    return;
                }

                filledAnswers.push(answerText.value.trim());
                if (isCorrect) {
                    hasCorrect = true;
                }
            });

            if (filledAnswers.length < 2 || !hasCorrect) {
                valid = false;
                question.classList.add('error-border');
            }

            if (answers.length > MAX_ANSWERS) {
                alert(`У одного из вопросов больше ${MAX_ANSWERS} вариантов ответа. Для автосканирования это не поддерживается.`);
                valid = false;
            }
        });

        const criteria = collectGradeCriteria();
        if (!criteria.length) {
            alert('Добавьте хотя бы один критерий оценивания');
            return false;
        }

        criteria.forEach((criterion, index) => {
            const block = document.querySelectorAll('.grade-criterion')[index];
            if (!criterion.label) {
                block.querySelector('.criterion-label').classList.add('error-border');
                valid = false;
            }
        });

        return valid;
    }

    function collectQuestions() {
        return Array.from(document.querySelectorAll('.question-item')).map((question, index) => ({
            question_text: question.querySelector('.question-text').value.trim(),
            type: question.querySelector('.question-type').value,
            points: parseInt(question.querySelector('.question-points').value, 10) || 1,
            variant_number: Math.max(1, Math.min(
                normalizeVariantCountValue(),
                parseInt(question.querySelector('.question-variant')?.value, 10) || 1
            )),
            order: index,
            answers: Array.from(question.querySelectorAll('.answer-item')).map((answer, answerIndex) => ({
                answer_text: answer.querySelector('.answer-text').value.trim(),
                is_correct: answer.querySelector('.answer-correct').checked,
                order: answerIndex
            }))
        }));
    }

    document.getElementById('testForm').addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!validateForm()) {
            const firstError = document.querySelector('.error-border');
            firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const payload = {
            title: document.getElementById('title').value.trim(),
            subject_name: document.getElementById('subject_name').value.trim(),
            description: document.getElementById('description').value.trim() || null,
            time_limit: document.getElementById('time_limit').value ? parseInt(document.getElementById('time_limit').value, 10) : null,
            variant_count: parseInt(document.getElementById('variant_count').value, 10) || 1,
            is_active: document.getElementById('is_active').value === '1',
            grade_criteria: collectGradeCriteria(),
            questions: collectQuestions()
        };

        try {
            const response = await authApiFetch('/api/tests', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Не удалось создать тест');
            }

            const data = await response.json();
            window.location.href = `/tests/${data.data.id}`;
        } catch (error) {
            alert(error.message || 'Ошибка соединения с сервером');
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.classList.contains('question-points') || event.target.classList.contains('question-variant')) {
            updateTotalPoints();
        }
    });

    document.getElementById('variant_count').addEventListener('input', () => {
        refreshQuestionVariantOptions();
    });

    document.getElementById('questionsImportInput').addEventListener('change', async (event) => {
        const [file] = event.target.files || [];
        event.target.value = '';

        try {
            await importQuestionsFromFile(file);
        } catch (error) {
            alert(error.message || 'Ошибка импорта');
        }
    });

    document.addEventListener('DOMContentLoaded', async () => {
        if (!await ensureAuthenticatedPage()) {
            return;
        }

        addQuestion();
        refreshQuestionVariantOptions();
        fillSuggestedCriteria();
    });
</script>
</body>
</html>
