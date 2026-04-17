<!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Редактирование теста'])
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'>
    <style>
        .error-border {
            border-color: #ef4444 !important;
            background-color: #fff1f2 !important;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div id="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sky-500"></div>
        <p class="text-slate-600 mt-4 dark:text-slate-300">Загружаю тест для редактирования...</p>
    </div>

    <div id="pageContent" class="hidden">
        <div class="mb-8">
            <p class="text-sm uppercase tracking-[0.3em] text-sky-700 font-semibold">Редактирование</p>
            <h1 class="text-3xl font-bold mt-2">Настройка теста</h1>
            <p class="text-slate-600 mt-2 dark:text-slate-400">Измените предмет, вопросы, баллы и критерии оценивания. Новые пороги будут использоваться при следующих проверках сканов.</p>
        </div>

        <form id="testForm" class="grid items-start gap-6 xl:grid-cols-[260px_minmax(0,1fr)_320px]">
            <aside class="hidden xl:block xl:sticky xl:top-24">
                <section class="bg-white rounded-3xl border border-slate-200 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Навигация</div>
                    <div class="mt-4 space-y-2">
                        <button type="button" onclick="scrollToComposerSection('detailsSection')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Основная информация</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" onclick="scrollToComposerSection('importSection')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Импорт</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" onclick="scrollToComposerSection('gradingSection')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Шкала оценивания</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" onclick="scrollToComposerSection('questionsSection')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Вопросы</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                    </div>
                </section>
            </aside>

            <div class="space-y-6">
            <section id="detailsSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 space-y-4 dark:bg-slate-900 dark:border-slate-800 dark:shadow-none">
                <div class="flex flex-wrap justify-between gap-4 items-center">
                    <h2 class="text-xl font-semibold">Основная информация</h2>
                    <div class="text-sm text-slate-500 dark:text-slate-400">
                        Формат бланка: неограниченное число вопросов и до <span class="font-semibold text-slate-700 dark:text-slate-200">4 вариантов ответа</span> на вопрос
                    </div>
                </div>

                <div>
                    <label for="subject_name" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Предмет</label>
                    <input id="subject_name" type="text" required
                           class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Название теста</label>
                    <input id="title" type="text" required
                           class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Описание</label>
                    <textarea id="description" rows="3"
                              class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></textarea>
                </div>

                <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div>
                        <label for="time_limit" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Время выполнения</label>
                        <input id="time_limit" type="number" min="1"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>

                    <div>
                        <label for="variant_count" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Количество вариантов</label>
                        <input id="variant_count" type="number" min="1" max="10"
                               class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <p class="text-xs text-slate-500 mt-2 dark:text-slate-400">До 10 вариантов. Уже выпущенные бланки сохранят свои номера вариантов.</p>
                    </div>

                    <div>
                        <label for="test_status" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Статус</label>
                        <select id="test_status" class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            <option value="active">Активен</option>
                            <option value="draft">Черновик</option>
                            <option value="closed">Закрыт</option>
                        </select>
                    </div>

                    <div>
                        <label for="delivery_mode" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Формат проведения</label>
                        <select id="delivery_mode" class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            <option value="blank">Только на бланках</option>
                            <option value="electronic">Только электронно</option>
                            <option value="hybrid">Совмещённый режим</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-2 dark:text-slate-400">В электронном и совмещённом режиме тест можно запускать по группе и проходить по ссылке или коду.</p>
                    </div>
                </div>
            </section>

            <section id="importSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 space-y-4 dark:bg-slate-900 dark:border-slate-800 dark:shadow-none">
                <div class="flex flex-wrap justify-between items-start gap-4">
                    <div>
                        <h2 class="text-xl font-semibold">Импорт вопросов</h2>
                        <p class="text-slate-500 mt-1 dark:text-slate-400">Можно быстро заменить текущий набор вопросов или добавить новые из `JSON` или `XLSX`.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <input id="questionsImportInput" type="file" accept=".json,.xlsx" class="hidden">
                        <button type="button" onclick="document.getElementById('questionsImportInput').click()" class="bg-violet-600 text-white px-4 py-2 rounded-xl hover:bg-violet-500 transition flex items-center gap-2">
                            <i class="fas fa-file-import"></i>
                            Импортировать файл
                        </button>
                    </div>
                </div>

                <div class="grid lg:grid-cols-[1.15fr_0.85fr] gap-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-300">
                        <div class="font-semibold text-slate-900 mb-2 dark:text-white">JSON</div>
                        <div>Поддерживаются старый формат без вариантов и новый формат с полями <code>variant</code> и <code>order</code> у каждого вопроса. Если <code>variant</code> нет, вопрос автоматически считается частью варианта <code>1</code>.</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-300">
                        <div class="font-semibold text-slate-900 mb-2 dark:text-white">XLSX</div>
                        <div>Первая строка должна содержать заголовки <code>question_text</code>, <code>order</code>, <code>variant</code>, <code>type</code>, <code>points</code>, <code>answer_a</code> ... <code>answer_d</code>, <code>correct</code>.</div>
                    </div>
                </div>

                <div id="importStatus" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200"></div>
            </section>

            <section id="gradingSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 dark:bg-slate-900 dark:border-slate-800 dark:shadow-none">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <div>
                        <h2 class="text-xl font-semibold">Критерии оценивания</h2>
                        <p class="text-slate-500 mt-1 dark:text-slate-400">Измените пороги по баллам при необходимости.</p>
                    </div>
                    <button type="button" onclick="fillSuggestedCriteria()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl transition dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                        Обновить рекомендуемые пороги
                    </button>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4" id="gradeCriteriaContainer"></div>

                <button type="button" onclick="addGradeCriterion()" class="text-sky-600 hover:text-sky-800 text-sm font-medium flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i>
                    Добавить уровень оценки
                </button>

                <div class="mt-4 bg-sky-50 border border-sky-100 rounded-2xl p-4 text-sm text-sky-900 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-200">
                    <span id="totalPointsLabel">Текущий максимальный балл:</span>
                    <span id="totalPointsSummary" class="font-semibold">0</span>
                </div>
            </section>

            <section id="questionsSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 dark:bg-slate-900 dark:border-slate-800 dark:shadow-none">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                    <div>
                        <h2 class="text-xl font-semibold">Вопросы</h2>
                        <p class="text-slate-500 mt-1 dark:text-slate-400">Изменения сразу повлияют на новые проверки. Если вопросов будет много, бланк ответов сам продолжится на следующих листах.</p>
                    </div>
                    <button type="button" onclick="addQuestion()" class="bg-emerald-600 text-white px-4 py-2 rounded-xl hover:bg-emerald-500 transition flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        Добавить вопрос
                    </button>
                </div>

                <div id="questionsContainer" class="space-y-5"></div>
                <div id="noQuestions" class="text-center py-10 border-2 border-dashed border-slate-300 rounded-2xl text-slate-500 hidden dark:border-slate-700 dark:text-slate-400 dark:bg-slate-950/40">
                    В тесте не осталось вопросов.
                </div>
            </section>
            </div>

            <aside class="space-y-4 xl:sticky xl:top-24">
                <section class="proverium-panel rounded-3xl border border-slate-200 p-5 dark:border-slate-800">
                    <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Панель действий</div>
                    <h2 class="mt-3 text-xl font-semibold text-slate-900 dark:text-white">Редактирование теста</h2>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Основные действия вынесены в боковую панель, чтобы не листать страницу до самого низа.</p>

                    <div class="mt-5 space-y-3">
                        <button type="button" onclick="addQuestion()" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 font-medium text-white transition hover:bg-emerald-500">
                            <i class="fas fa-plus"></i>
                            Добавить вопрос
                        </button>
                        <button type="button" onclick="document.getElementById('questionsImportInput').click()" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-violet-600 px-4 py-3 font-medium text-white transition hover:bg-violet-500">
                            <i class="fas fa-file-import"></i>
                            Импортировать вопросы
                        </button>
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-sky-600 px-4 py-3 font-medium text-white transition hover:bg-sky-500">
                            <i class="fas fa-save"></i>
                            Сохранить изменения
                        </button>
                        <button type="button" onclick="window.location.href=`/tests/${testId}`" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-700 px-4 py-3 font-medium text-white transition hover:bg-slate-600 dark:bg-slate-800 dark:hover:bg-slate-700">
                            <i class="fas fa-arrow-left"></i>
                            Вернуться к тесту
                        </button>
                    </div>
                </section>

                <section class="bg-white rounded-3xl border border-slate-200 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Сводка</div>
                    <div class="mt-4 space-y-3">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950/70">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Вопросов</div>
                            <div id="sidebarQuestionCount" class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">0</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950/70">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Варианты</div>
                            <div id="sidebarVariantCount" class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">1</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950/70">
                            <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Баллы</div>
                            <div id="sidebarScoreSummary" class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">0</div>
                        </div>
                    </div>
                </section>

            </aside>
        </form>
    </div>
</div>

<template id="questionTemplate">
    <article class="question-item border border-slate-200 rounded-3xl p-5 bg-slate-50 dark:border-slate-700 dark:bg-slate-950/70" data-id="">
        <div class="flex justify-between items-start gap-3 mb-4">
            <h3 class="text-lg font-semibold text-slate-800 question-title dark:text-white"></h3>
            <button type="button" onclick="removeQuestion(this)" class="text-rose-600 hover:text-rose-800">
                <i class="fas fa-trash"></i>
            </button>
        </div>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Текст вопроса</label>
                <input type="text" class="question-text w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Тип вопроса</label>
                    <select class="question-type w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="single">Один правильный ответ</option>
                        <option value="multiple">Несколько правильных ответов</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Баллы</label>
                    <input type="number" class="question-points w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500" min="1" value="1">
                </div>

                <div class="question-variant-wrapper hidden">
                    <label class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Вариант</label>
                    <select class="question-variant w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></select>
                    <p class="question-variant-hint text-xs text-slate-500 mt-2 dark:text-slate-400">Этот вопрос попадет в выбранный вариант теста.</p>
                </div>
            </div>

            <div>
                <div class="flex justify-between items-center gap-3 mb-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Варианты ответов</label>
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
    <div class="answer-item flex items-center gap-3" data-id="">
        <input type="radio" class="answer-correct w-4 h-4 text-sky-600 border-slate-300">
        <input type="text" class="answer-text flex-1 px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
        <button type="button" onclick="removeAnswer(this)" class="text-rose-600 hover:text-rose-800">
            <i class="fas fa-times"></i>
        </button>
    </div>
</template>

<template id="gradeCriterionTemplate">
    <div class="grade-criterion bg-slate-50 border border-slate-200 rounded-2xl p-4 dark:border-slate-700 dark:bg-slate-950/70">
        <div class="flex justify-between items-center gap-3 mb-3">
            <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Оценка</label>
            <button type="button" onclick="removeGradeCriterion(this)" class="text-rose-500 hover:text-rose-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <input type="text" class="criterion-label w-full px-3 py-2 rounded-xl border border-slate-300 mb-3 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
        <input type="number" min="0" class="criterion-min-points w-full px-3 py-2 rounded-xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
    </div>
</template>

<script>
    const testId = {{ $id }};
    const MAX_ANSWERS = 4;
    const DEFAULT_VARIANT_COUNT = 1;
    let currentTest = null;

    function createUid() {
        return `q_${Date.now()}_${Math.random().toString(16).slice(2)}`;
    }

    function getVariantCountInputValue() {
        return String(document.getElementById('variant_count').value || '').trim();
    }

    function hasVariantCountValue(value = null) {
        return String(value ?? getVariantCountInputValue()).trim() !== '';
    }

    function normalizeVariantCountValue(value = null, fallback = DEFAULT_VARIANT_COUNT) {
        const source = String(value ?? getVariantCountInputValue()).trim();
        const parsed = parseInt(source, 10);

        if (!Number.isInteger(parsed)) {
            return fallback;
        }

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
        document.querySelectorAll('.question-item').forEach((questionItem) => {
            renderQuestionVariantOptions(
                questionItem,
                questionItem.querySelector('.question-variant')?.value || 1
            );
        });

        updateTotalPoints();
    }

    async function apiFetch(url, options = {}) {
        return authApiFetch(url, options);
    }

    async function loadTest() {
        const response = await apiFetch(`/api/tests/${testId}`);
        if (!response.ok) {
            throw new Error('Не удалось загрузить тест');
        }

        currentTest = (await response.json()).data;
        fillForm();

        document.getElementById('loading').classList.add('hidden');
        document.getElementById('pageContent').classList.remove('hidden');
    }

    function fillForm() {
        document.getElementById('subject_name').value = currentTest.subject_name || currentTest.title || '';
        document.getElementById('title').value = currentTest.title || '';
        document.getElementById('description').value = currentTest.description || '';
        document.getElementById('time_limit').value = currentTest.time_limit || '';
        document.getElementById('variant_count').value = currentTest.variant_count || 1;
        document.getElementById('test_status').value = currentTest.test_status || (currentTest.is_active ? 'active' : 'draft');
        document.getElementById('delivery_mode').value = currentTest.delivery_mode || 'blank';

        document.getElementById('questionsContainer').innerHTML = '';
        (currentTest.questions || []).forEach((question) => addQuestion(question, { reveal: false }));

        document.getElementById('gradeCriteriaContainer').innerHTML = '';
        (currentTest.grade_criteria || []).forEach((criterion) => addGradeCriterion(criterion));
        if (!(currentTest.grade_criteria || []).length) {
            fillSuggestedCriteria();
        }

        updateTotalPoints();
        renderComposerSidebarState();
    }

    function addQuestion(question = null, { reveal = question === null } = {}) {
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

        if (reveal) {
            insertedQuestion.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
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

        renderComposerSidebarState();
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

    function getVariantScoreTotals(variantCount = normalizeVariantCountValue()) {
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
            label.textContent = 'Текущий максимальный балл:';
            summary.textContent = String(getTotalPoints());
            renderComposerSidebarState();
            return;
        }

        label.textContent = 'Баллы по вариантам:';
        summary.textContent = getVariantScoreTotals()
            .map((value, index) => `В${index + 1}: ${value}`)
            .join(' • ');
        renderComposerSidebarState();
    }

    function renderComposerSidebarState() {
        const questionCount = document.querySelectorAll('.question-item').length;
        const variantCount = normalizeVariantCountValue();
        const variantCountSpecified = hasVariantCountValue();
        const scoreSummary = variantCount <= 1
            ? `${getTotalPoints()} балл.`
            : getVariantScoreTotals()
                .map((value, index) => `В${index + 1}: ${value}`)
                .join(' • ');

        const questionCountElement = document.getElementById('sidebarQuestionCount');
        const variantCountElement = document.getElementById('sidebarVariantCount');
        const scoreSummaryElement = document.getElementById('sidebarScoreSummary');

        if (questionCountElement) {
            questionCountElement.textContent = String(questionCount);
        }

        if (variantCountElement) {
            variantCountElement.textContent = variantCountSpecified ? String(variantCount) : '—';
        }

        if (scoreSummaryElement) {
            scoreSummaryElement.textContent = scoreSummary || '0';
        }
    }

    function scrollToComposerSection(sectionId) {
        document.getElementById(sectionId)?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
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

    function getImportedRequiredVariantCount(imported) {
        const metadataVariantCount = normalizeVariantCountValue(imported?.variant_count);
        const questionVariants = (imported?.questions || []).map((question) => (
            parseInt(question?.variant_number ?? question?.variant ?? 1, 10) || 1
        ));
        const highestQuestionVariant = questionVariants.length ? Math.max(...questionVariants) : DEFAULT_VARIANT_COUNT;

        return Math.max(metadataVariantCount, highestQuestionVariant);
    }

    function syncVariantCountFromImportedData(imported, { replaceExisting = false } = {}) {
        const currentVariantCount = normalizeVariantCountValue();
        const requiredVariantCount = getImportedRequiredVariantCount(imported);
        const nextVariantCount = replaceExisting ? requiredVariantCount : Math.max(currentVariantCount, requiredVariantCount);

        if (nextVariantCount === currentVariantCount) {
            return;
        }

        document.getElementById('variant_count').value = String(nextVariantCount);
        refreshQuestionVariantOptions();
    }

    function applyImportedTestData(imported) {
        const importedQuestions = imported.questions || [];
        if (!importedQuestions.length) {
            throw new Error('В файле не найдено ни одного вопроса');
        }

        const shouldReplace = confirm('Заменить текущие вопросы импортируемыми? Нажмите "Отмена", чтобы добавить новые вопросы в конец.');

        if (shouldReplace) {
            document.getElementById('questionsContainer').innerHTML = '';
            document.getElementById('noQuestions').classList.add('hidden');
        }

        syncVariantCountFromImportedData(imported, { replaceExisting: shouldReplace });
        importedQuestions.forEach((question) => addQuestion(question, { reveal: false }));
        maybeApplyImportedMetadata(imported);
        renderImportStatus(importedQuestions.length, shouldReplace);
    }

    function maybeApplyImportedMetadata(imported) {
        syncVariantCountFromImportedData(imported);
        if (imported.subject_name && confirm('В файле найден предмет. Подставить его в форму?')) {
            document.getElementById('subject_name').value = imported.subject_name;
        }

        if (imported.title && confirm('В файле найдено название теста. Подставить его в форму?')) {
            document.getElementById('title').value = imported.title;
        }

        if (imported.description && confirm('В файле найдено описание. Подставить его в форму?')) {
            document.getElementById('description').value = imported.description;
        }

        if (imported.time_limit && confirm('В файле найден лимит времени. Подставить его в форму?')) {
            document.getElementById('time_limit').value = imported.time_limit;
        }

        if (imported.delivery_mode && ['blank', 'electronic', 'hybrid'].includes(imported.delivery_mode) && confirm('В файле найден формат проведения. Подставить его в форму?')) {
            document.getElementById('delivery_mode').value = imported.delivery_mode;
        }

        if (imported.variant_count && confirm('В файле найдено количество вариантов. Подставить его в форму?')) {
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

    function collectQuestions() {
        return Array.from(document.querySelectorAll('.question-item')).map((question, index) => {
            const questionId = question.dataset.id;
            const questionPayload = {
                question_text: question.querySelector('.question-text').value.trim(),
                type: question.querySelector('.question-type').value,
                points: parseInt(question.querySelector('.question-points').value, 10) || 1,
                variant_number: Math.max(1, Math.min(
                    normalizeVariantCountValue(),
                    parseInt(question.querySelector('.question-variant')?.value, 10) || 1
                )),
                order: index,
                answers: Array.from(question.querySelectorAll('.answer-item')).map((answer, answerIndex) => {
                    const answerPayload = {
                        answer_text: answer.querySelector('.answer-text').value.trim(),
                        is_correct: answer.querySelector('.answer-correct').checked,
                        order: answerIndex
                    };

                    if (answer.dataset.id && !String(answer.dataset.id).startsWith('q_')) {
                        answerPayload.id = parseInt(answer.dataset.id, 10);
                    }

                    return answerPayload;
                })
            };

            if (questionId && !String(questionId).startsWith('q_')) {
                questionPayload.id = parseInt(questionId, 10);
            }

            return questionPayload;
        });
    }

    function validateForm() {
        let valid = true;
        document.querySelectorAll('.error-border').forEach((item) => item.classList.remove('error-border'));

        if (!document.getElementById('subject_name').value.trim()) {
            document.getElementById('subject_name').classList.add('error-border');
            valid = false;
        }

        if (!document.getElementById('title').value.trim()) {
            document.getElementById('title').classList.add('error-border');
            valid = false;
        }

        const variantCountInput = document.getElementById('variant_count');
        const variantCountRaw = String(variantCountInput.value || '').trim();
        const variantCount = parseInt(variantCountRaw, 10);
        if (!variantCountRaw || !Number.isInteger(variantCount) || variantCount < 1 || variantCount > 10) {
            document.getElementById('variant_count').classList.add('error-border');
            valid = false;
        }

        const resolvedVariantCount = Number.isInteger(variantCount) ? variantCount : DEFAULT_VARIANT_COUNT;

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
            let hasCorrect = false;

            if (!questionText.value.trim()) {
                questionText.classList.add('error-border');
                valid = false;
            }

            if ((parseInt(points.value, 10) || 0) < 1) {
                points.classList.add('error-border');
                valid = false;
            }

            if ((parseInt(questionVariant?.value, 10) || 1) < 1 || (parseInt(questionVariant?.value, 10) || 1) > resolvedVariantCount) {
                questionVariant?.classList.add('error-border');
                valid = false;
            }

            answers.forEach((answer) => {
                const answerText = answer.querySelector('.answer-text');
                if (!answerText.value.trim()) {
                    answerText.classList.add('error-border');
                    valid = false;
                }
                if (answer.querySelector('.answer-correct').checked) {
                    hasCorrect = true;
                }
            });

            if (answers.length < 2 || !hasCorrect) {
                question.classList.add('error-border');
                valid = false;
            }

            if (answers.length > MAX_ANSWERS) {
                alert(`У одного из вопросов больше ${MAX_ANSWERS} вариантов ответа. Для автосканирования это не поддерживается.`);
                valid = false;
            }
        });

        collectGradeCriteria().forEach((criterion, index) => {
            if (!criterion.label) {
                document.querySelectorAll('.criterion-label')[index].classList.add('error-border');
                valid = false;
            }
        });

        if (resolvedVariantCount > 1) {
            const scoreTotals = getVariantScoreTotals(resolvedVariantCount);
            if (new Set(scoreTotals).size > 1) {
                document.getElementById('variant_count').classList.add('error-border');
                alert(`У всех вариантов должна быть одинаковая сумма баллов. Сейчас: ${scoreTotals.map((value, index) => `В${index + 1}: ${value}`).join(', ')}`);
                valid = false;
            }
        }

        return valid;
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
            variant_count: parseInt(document.getElementById('variant_count').value, 10),
            test_status: document.getElementById('test_status').value,
            delivery_mode: document.getElementById('delivery_mode').value,
            grade_criteria: collectGradeCriteria(),
            questions: collectQuestions()
        };

        try {
            const response = await apiFetch(`/api/tests/${testId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                const message = error.errors
                    ? Object.values(error.errors).flat().join(', ')
                    : (error.message || 'Не удалось сохранить изменения');
                throw new Error(message);
            }

            window.location.href = `/tests/${testId}`;
        } catch (error) {
            alert(error.message || 'Ошибка сохранения');
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target.classList.contains('question-points') || event.target.classList.contains('question-variant')) {
            updateTotalPoints();
        }
    });

    document.getElementById('variant_count').addEventListener('input', () => {
        refreshQuestionVariantOptions();
        updateTotalPoints();
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

        loadTest().catch((error) => {
            console.error(error);
            alert(error.message || 'Ошибка загрузки');
        });
    });
</script>
</body>
</html>
