<!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Тест'])
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css'>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div id="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sky-500"></div>
        <p class="text-slate-600 mt-4 dark:text-slate-300">Загружаю тест...</p>
    </div>

    <div id="pageContent" class="hidden space-y-6">
        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 dark:bg-slate-900 dark:border-slate-800">
            <div class="flex flex-wrap justify-between items-start gap-4">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-sky-700 font-semibold">Карточка теста</p>
                    <h1 id="testTitle" class="text-3xl font-bold mt-2"></h1>
                    <div id="testSubject" class="text-slate-500 mt-2 font-medium dark:text-slate-400"></div>
                    <p id="testDescription" class="text-slate-600 mt-3 max-w-3xl dark:text-slate-300"></p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button onclick="window.location.href=`/tests/${testId}/edit`" class="bg-sky-600 text-white px-4 py-3 rounded-2xl hover:bg-sky-500 transition flex items-center gap-2">
                        <i class="fas fa-pen"></i>
                        Редактировать
                    </button>
                    <button id="launchElectronicButton" onclick="scrollToTestSection('electronicSection')" class="bg-indigo-600 text-white px-4 py-3 rounded-2xl hover:bg-indigo-500 transition flex items-center gap-2">
                        <i class="fas fa-display"></i>
                        Провести тест электронно
                    </button>
                    <button onclick="downloadTestExport('json')" class="bg-violet-600 text-white px-4 py-3 rounded-2xl hover:bg-violet-500 transition flex items-center gap-2">
                        <i class="fas fa-file-code"></i>
                        Экспорт JSON
                    </button>
                    <button onclick="downloadTestExport('xlsx')" class="bg-emerald-600 text-white px-4 py-3 rounded-2xl hover:bg-emerald-500 transition flex items-center gap-2">
                        <i class="fas fa-file-excel"></i>
                        Экспорт Excel
                    </button>
                    <button onclick="openPrintPreview()" class="bg-sky-600 text-white px-4 py-3 rounded-2xl hover:bg-sky-500 transition flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        Предпросмотр печати
                    </button>
                    <button onclick="window.location.href='/tests'" class="bg-slate-700 text-white px-4 py-3 rounded-2xl hover:bg-slate-600 transition dark:bg-slate-800 dark:hover:bg-slate-700">
                        Назад
                    </button>
                </div>
            </div>

            <div class="grid md:grid-cols-3 xl:grid-cols-6 gap-4 mt-6">
                <div class="bg-slate-50 rounded-2xl p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Вопросы</div>
                    <div id="questionCount" class="text-2xl font-bold mt-2">0</div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Макс. балл</div>
                    <div id="maxPoints" class="text-2xl font-bold mt-2">0</div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Время</div>
                    <div id="timeLimit" class="text-2xl font-bold mt-2">Без лимита</div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Статус</div>
                    <div id="testStatus" class="text-2xl font-bold mt-2">Черновик</div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Варианты</div>
                    <div id="variantCount" class="text-2xl font-bold mt-2">1</div>
                </div>
                <div class="bg-slate-50 rounded-2xl p-4 dark:bg-slate-950/70">
                    <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Формат</div>
                    <div id="deliveryMode" class="mt-2 max-w-full break-words text-xl font-bold leading-tight xl:text-2xl">На бланках</div>
                </div>
            </div>
        </section>

        <div class="grid items-start gap-6 xl:grid-cols-[220px_minmax(0,1fr)]">
            <aside class="hidden xl:block xl:sticky xl:top-24">
                <section class="proverium-panel rounded-3xl border border-slate-200 p-4 dark:border-slate-800">
                    <div class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500 dark:text-slate-400">Навигация</div>
                    <div class="mt-4 space-y-2">
                        <button type="button" onclick="scrollToTestSection('pageContent')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Карточка теста</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" onclick="scrollToTestSection('questionsSection')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Вопросы</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" onclick="scrollToTestSection('gradingSection')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Шкала</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" onclick="scrollToTestSection('electronicSection')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Электронный режим</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" onclick="scrollToTestSection('blankGenerationSection')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Выпуск бланков</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" onclick="scrollToTestSection('scanUploadSection')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Загрузка сканов</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" onclick="scrollToTestSection('blankFormsSection')" class="flex w-full items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            <span>Персональные бланки</span>
                            <i class="fas fa-arrow-down"></i>
                        </button>
                    </div>
                </section>
            </aside>

            <div class="space-y-6">
                <section id="questionsOverviewSection" class="grid xl:grid-cols-[1.2fr_0.8fr] gap-6">
                    <div id="questionsSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 dark:bg-slate-900 dark:border-slate-800">
                        <div class="flex justify-between items-center gap-3 mb-4">
                            <div>
                                <h2 class="text-xl font-semibold dark:text-white">Вопросы</h2>
                                <p class="text-slate-500 mt-1 dark:text-slate-400">Вопросы и правильные ответы теста.</p>
                            </div>
                        </div>

                        <div id="questionsList" class="space-y-4"></div>
                    </div>

                    <div class="space-y-6">
                        <section id="gradingSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 dark:bg-slate-900 dark:border-slate-800">
                            <h2 class="text-xl font-semibold">Шкала оценивания</h2>
                            <p class="text-slate-500 mt-1 dark:text-slate-400">Эти пороги применяются при автоматической проверке сканов.</p>
                            <div id="gradeCriteriaList" class="mt-4 space-y-3"></div>
                        </section>

                        <section id="scanInfoSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 dark:bg-slate-900 dark:border-slate-800">
                            <h2 class="text-xl font-semibold">Сканирование</h2>
                            <div id="scanSupportNote" class="mt-3 text-sm rounded-2xl border border-amber-200 bg-amber-50 text-amber-800 p-4 hidden dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"></div>
                            <div class="mt-4 text-sm text-slate-600 dark:text-slate-300">
                                Поддерживаются <span class="font-semibold">JPG / PNG / WEBP / PDF</span>. Если загружен PDF, браузер автоматически преобразует все его листы в изображения перед отправкой. Чужие бланки тоже можно распознать, но оценку им поставить нельзя.
                            </div>
                        </section>
                    </div>
                </section>

                <section id="electronicSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 dark:bg-slate-900 dark:border-slate-800">
                    <div class="flex flex-wrap justify-between items-start gap-4">
                        <div>
                            <h2 class="text-xl font-semibold">Электронное тестирование</h2>
                            <p id="electronicSectionDescription" class="text-slate-500 mt-1 dark:text-slate-400">Запустите прохождение по группе, выдайте ссылку или код и отслеживайте завершённые работы.</p>
                        </div>
                        <div id="electronicLiveBadge" class="hidden rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200"></div>
                    </div>

                    <div class="mt-5 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                        <div class="space-y-5">
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-950/60">
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="electronicGroupSelect" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Группа для запуска</label>
                                        <select id="electronicGroupSelect" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700"></select>
                                    </div>
                                    <div>
                                        <label for="electronicVariantMode" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Распределение вариантов</label>
                                        <select id="electronicVariantMode" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700">
                                            <option value="same">Всем один вариант</option>
                                            <option value="balanced">Распределить поровну</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="electronicSameVariantWrap" class="mt-4">
                                    <label for="electronicVariantNumber" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Номер варианта</label>
                                    <select id="electronicVariantNumber" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700"></select>
                                </div>

                                <div class="mt-5 flex flex-wrap gap-3">
                                    <button id="startElectronicSessionButton" type="button" onclick="startElectronicSession()" class="rounded-2xl bg-indigo-600 px-5 py-3 font-medium text-white transition hover:bg-indigo-500">
                                        Запустить по группе
                                    </button>
                                    <button type="button" onclick="copyElectronicCode()" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                                        Копировать код
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-950/60">
                                <div class="grid gap-4 xl:grid-cols-[220px_minmax(0,1fr)]">
                                    <div class="min-w-0">
                                        <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Код теста</div>
                                        <div id="electronicAccessCode" class="mt-2 text-2xl font-bold tracking-[0.26em]">—</div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Общая ссылка</div>
                                        <a id="electronicGeneralLink" href="#" target="_blank" rel="noopener noreferrer" class="mt-2 block break-all text-sm leading-5 font-medium text-sky-600 hover:text-sky-800 dark:text-sky-300 dark:hover:text-sky-200">Сначала запустите тест</a>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-950/60">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Персональные ссылки</div>
                                        <div class="mt-2 text-sm text-slate-600 dark:text-slate-300">Для каждого ученика группы можно открыть отдельную ссылку с уже заданным ФИО.</div>
                                    </div>
                                </div>
                                <div id="electronicMembersList" class="mt-4 space-y-3"></div>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-950/60">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Завершённые работы</div>
                                        <div class="mt-2 text-sm text-slate-600 dark:text-slate-300">Преподаватель видит логи активности, ответы и может сразу поставить оценку.</div>
                                    </div>
                                    <div id="electronicAttemptsSummary" class="text-sm font-medium text-slate-500 dark:text-slate-400">Нет активного запуска</div>
                                </div>
                                <div id="electronicAttemptsList" class="mt-4 space-y-3"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="workflow" class="grid xl:grid-cols-[0.95fr_1.05fr] gap-6">
                    <div class="space-y-6">
                        <section id="blankGenerationSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 dark:bg-slate-900 dark:border-slate-800">
                            <div class="flex justify-between items-center gap-3">
                                <div>
                                    <h2 class="text-xl font-semibold">Выпуск бланков</h2>
                                    <p class="text-slate-500 mt-1 dark:text-slate-400">Сгенерируйте персональные бланки для всей группы или только для отмеченных студентов.</p>
                                </div>
                                <button onclick="window.location.href='/groups'" class="text-sky-600 hover:text-sky-800 text-sm font-medium">
                                    Открыть группы
                                </button>
                            </div>

                            <div class="mt-5 space-y-4">
                                <div>
                                    <label for="groupSelect" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Группа</label>
                                    <select id="groupSelect" onchange="handleGroupChange()" class="w-full px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500"></select>
                                </div>

                                <div>
                                    <div class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Кому выпускать бланки</div>
                                    <div class="grid sm:grid-cols-2 gap-3">
                                        <button id="generateModeAllButton" type="button" onclick="setBlankGenerationMode('all')" class="rounded-2xl border border-slate-300 px-4 py-3 text-left transition hover:border-sky-300 hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:bg-slate-900">
                                            <div class="font-semibold">Вся группа</div>
                                            <div class="text-sm mt-1 opacity-80">Бланки будут созданы для каждого студента выбранной группы.</div>
                                        </button>
                                        <button id="generateModeSelectedButton" type="button" onclick="setBlankGenerationMode('selected')" class="rounded-2xl border border-slate-300 px-4 py-3 text-left transition hover:border-sky-300 hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:bg-slate-900">
                                            <div class="font-semibold">Только выбранные</div>
                                            <div class="text-sm mt-1 opacity-80">Ниже можно отметить только тех студентов, кому нужны бланки сейчас.</div>
                                        </button>
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/70">
                                    <div class="text-sm uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Варианты</div>
                                    <div id="variantAssignmentSummary" class="text-sm text-slate-600 mt-2 dark:text-slate-400">Для этого теста используется один вариант.</div>
                                    <div id="variantAssignmentSettings" class="mt-4 space-y-3"></div>
                                </div>

                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/70">
                                    <div class="flex flex-wrap justify-between items-start gap-3">
                                        <div>
                                            <div class="text-sm uppercase tracking-[0.25em] text-slate-400 dark:text-slate-500">Состав группы</div>
                                            <div id="studentSelectionSummary" class="text-sm text-slate-600 mt-2 dark:text-slate-400">Сначала выберите группу.</div>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button id="selectAllStudentsButton" type="button" onclick="selectAllGroupStudents()" class="bg-white border border-slate-200 text-slate-700 px-3 py-2 rounded-xl hover:border-sky-300 hover:text-sky-700 transition text-sm dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                                                Отметить всех
                                            </button>
                                            <button id="clearStudentsButton" type="button" onclick="clearSelectedGroupStudents()" class="bg-white border border-slate-200 text-slate-700 px-3 py-2 rounded-xl hover:border-sky-300 hover:text-sky-700 transition text-sm dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                                                Снять выбор
                                            </button>
                                        </div>
                                    </div>
                                    <div id="groupStudentsList" class="mt-4 grid sm:grid-cols-2 gap-3"></div>
                                </div>

                                <div class="flex flex-wrap gap-3">
                                    <button id="generateBlankFormsButton" onclick="generateBlankForms()" class="bg-emerald-600 text-white px-5 py-3 rounded-2xl hover:bg-emerald-500 transition font-medium">
                                        Сгенерировать бланки
                                    </button>
                                    <div id="printGeneratedActions" class="hidden flex flex-wrap gap-3">
                                        <button onclick="printGeneratedPack()" class="bg-white border border-slate-200 text-slate-700 px-5 py-3 rounded-2xl hover:border-sky-300 hover:text-sky-700 transition font-medium dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                                            Печать пачки
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section id="scanUploadSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 dark:bg-slate-900 dark:border-slate-800">
                            <h2 class="text-xl font-semibold">Загрузка сканов</h2>
                            <p class="text-slate-500 mt-1 dark:text-slate-400">Загрузите отсканированные листы бланков, и система автоматически поставит баллы и оценку.</p>

                            <div class="mt-5 space-y-4">
                                <input id="scanFiles" type="file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf"
                                       class="w-full px-4 py-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                                <button id="scanButton" onclick="uploadScans()" class="bg-sky-600 text-white px-5 py-3 rounded-2xl hover:bg-sky-500 transition font-medium">
                                    Обработать сканы
                                </button>
                            </div>

                            <div id="scanResults" class="mt-5 space-y-3"></div>
                        </section>
                    </div>

                    <section id="blankFormsSection" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 dark:bg-slate-900 dark:border-slate-800">
                        <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                            <div>
                                <h2 class="text-xl font-semibold">Персональные бланки</h2>
                                <p class="text-slate-500 mt-1 dark:text-slate-400">Список уже выпущенных бланков и результатов проверки.</p>
                            </div>
                            <button id="bulkDeleteBlankFormsButton" onclick="deleteIssuedBlankForms()" class="hidden bg-rose-600 text-white px-4 py-2 rounded-xl hover:bg-rose-500 transition text-sm">
                                Удалить все выпущенные
                            </button>
                        </div>

                        <div id="blankFormsList" class="space-y-3"></div>
                    </section>
                </section>
            </div>

        </div>
    </div>
</div>

<script>
    const testId = {{ $id }};
    const SCAN_ROWS_PER_PAGE = 24;
    let currentTest = null;
    let groups = [];
    let blankForms = [];
    let electronicDashboard = null;
    let electronicKnownSubmittedAttemptIds = [];
    let electronicPollHandle = null;
    let lastGeneratedBlankIds = [];
    let blankGenerationMode = 'all';
    let selectedGroupStudentIds = [];
    let blankVariantDistributionMode = 'same';
    let sharedVariantNumber = 1;
    let customStudentVariants = {};
    let pdfJsLoadingPromise = null;

    async function apiFetch(url, options = {}) {
        return authApiFetch(url, options);
    }

    function getTestStatusValue(test = currentTest) {
        return test?.test_status || (test?.is_active ? 'active' : 'draft');
    }

    function getTestStatusLabel(test = currentTest) {
        if (test?.test_status_label) {
            return test.test_status_label;
        }

        switch (getTestStatusValue(test)) {
            case 'closed':
                return 'Закрыт';
            case 'draft':
                return 'Черновик';
            default:
                return 'Активен';
        }
    }

    function isCurrentTestClosed() {
        return getTestStatusValue(currentTest) === 'closed';
    }

    function setControlDisabledState(element, disabled, disabledTitle = '') {
        if (!element) {
            return;
        }

        element.disabled = disabled;
        element.title = disabled ? disabledTitle : '';
        element.classList.toggle('opacity-50', disabled);
        element.classList.toggle('cursor-not-allowed', disabled);
    }

    function resolveScanSupportState(options = {}) {
        const variantSummaries = options.variantSummaries || getVariantSummaries();
        const hasTooManyAnswers = options.hasTooManyAnswers ?? (currentTest.questions || []).some((question) => (question.answers || []).length > 5);
        const answerSheetPageCount = options.answerSheetPageCount ?? Math.max(
            1,
            ...variantSummaries.map((summary) => Math.max(1, Math.ceil(summary.questionCount / SCAN_ROWS_PER_PAGE)))
        );

        if (currentTest?.delivery_mode === 'electronic') {
            return {
                disabled: true,
                disabledTitle: 'Для этого теста сейчас включён только электронный режим.',
                noteVisible: true,
                noteText: 'Для этого теста сейчас включён только электронный режим. Чтобы сканировать бланки, переключите формат на бланки или совмещённый.',
            };
        }

        if (hasTooManyAnswers) {
            return {
                disabled: true,
                disabledTitle: 'В одном или нескольких вопросах больше 4 вариантов ответа.',
                noteVisible: true,
                noteText: 'В одном или нескольких вопросах больше 4 вариантов ответа. Текущий формат автосканирования поддерживает максимум 4.',
            };
        }

        if (answerSheetPageCount > 1) {
            return {
                disabled: false,
                disabledTitle: '',
                noteVisible: true,
                noteText: `Для самого длинного варианта этого теста понадобится ${answerSheetPageCount} листа(ов) ответов на каждого ученика. При проверке загружайте все листы ученика одной пачкой.`,
            };
        }

        return {
            disabled: false,
            disabledTitle: '',
            noteVisible: false,
            noteText: '',
        };
    }

    function syncScanControlsState(options = {}) {
        const scanSupportNote = document.getElementById('scanSupportNote');
        const scanButton = document.getElementById('scanButton');
        const scanFilesInput = document.getElementById('scanFiles');

        if (!scanSupportNote || !scanButton || !scanFilesInput) {
            return resolveScanSupportState(options);
        }

        const state = resolveScanSupportState(options);

        setControlDisabledState(scanButton, state.disabled, state.disabledTitle);
        scanFilesInput.disabled = state.disabled;
        scanFilesInput.title = state.disabled ? state.disabledTitle : '';
        scanFilesInput.classList.toggle('opacity-50', state.disabled);
        scanFilesInput.classList.toggle('cursor-not-allowed', state.disabled);

        scanSupportNote.textContent = state.noteText;
        scanSupportNote.classList.toggle('hidden', !state.noteVisible);

        return state;
    }

    async function updateTestDeliveryMode(deliveryMode) {
        const response = await apiFetch(`/api/tests/${testId}/delivery-mode`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                delivery_mode: deliveryMode,
            }),
        });

        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            const message = error.errors ? Object.values(error.errors).flat().join(', ') : (error.message || 'Не удалось изменить формат теста.');
            throw new Error(message);
        }

        const payload = await response.json();
        currentTest = payload.data || currentTest;
        renderTest();
        await loadElectronicDashboard();

        return currentTest;
    }

    function syncTestAvailabilityUi() {
        const isClosed = isCurrentTestClosed();
        const closedElectronicTitle = 'Тест закрыт. Новые электронные прохождения больше недоступны.';
        const closedBlankTitle = 'Тест закрыт. Новые бланки для него больше не выпускаются.';

        setControlDisabledState(document.getElementById('launchElectronicButton'), isClosed, closedElectronicTitle);
        setControlDisabledState(document.getElementById('startElectronicSessionButton'), isClosed, closedElectronicTitle);
        setControlDisabledState(document.getElementById('generateBlankFormsButton'), isClosed, closedBlankTitle);

        const description = document.getElementById('electronicSectionDescription');
        if (!description) {
            return;
        }

        if (isClosed) {
            description.textContent = 'Тест закрыт. Новые электронные прохождения запускать нельзя, но уже отправленные работы по-прежнему доступны для проверки.';
            return;
        }

        description.textContent = currentTest?.delivery_mode === 'blank'
            ? 'Этот тест пока работает только на бланках. Чтобы открыть электронное прохождение, переведите тест в электронный или совмещённый формат.'
            : 'Запустите прохождение по группе, выдайте ссылку или код и отслеживайте завершённые работы.';
    }

    async function loadPage() {
        try {
            const [testResponse, groupsResponse] = await Promise.all([
                apiFetch(`/api/tests/${testId}`),
                apiFetch('/api/student-groups')
            ]);

            if (!testResponse.ok) {
                throw new Error('Не удалось загрузить тест');
            }

            currentTest = (await testResponse.json()).data;
            groups = groupsResponse.ok ? ((await groupsResponse.json()).data || []) : [];

            renderTest();
            renderGroups();
            renderElectronicGroupSelect();
            try {
                await loadElectronicDashboard();
            } catch (electronicError) {
                console.error('Электронный блок временно недоступен:', electronicError);
                electronicDashboard = {
                    delivery_mode: currentTest?.delivery_mode || 'blank',
                    access_code: currentTest?.access_code || null,
                    current_session: null,
                };
                renderElectronicDashboard();
            }
            await loadBlankForms();

            document.getElementById('loading').classList.add('hidden');
            document.getElementById('pageContent').classList.remove('hidden');

            if (window.location.hash === '#workflow') {
                document.getElementById('workflow').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (error) {
            console.error(error);
            alert(error.message || 'Ошибка загрузки страницы');
        }
    }

    function renderTest() {
        document.getElementById('testTitle').textContent = currentTest.title || 'Без названия';
        document.getElementById('testSubject').textContent = currentTest.subject_name
            ? `Предмет: ${currentTest.subject_name}`
            : 'Предмет не указан';
        document.getElementById('testDescription').textContent = currentTest.description || 'Описание не указано';
        const variantSummaries = getVariantSummaries();
        const variantCount = normalizeVariantCount();
        const maxVariantQuestionCount = variantSummaries.length
            ? Math.max(...variantSummaries.map((summary) => summary.questionCount))
            : 0;
        const maxVariantScore = variantSummaries.length
            ? Math.max(...variantSummaries.map((summary) => summary.score))
            : 0;

        document.getElementById('questionCount').textContent = variantCount > 1
            ? `${maxVariantQuestionCount} / вариант`
            : (currentTest.questions?.length || 0);
        document.getElementById('maxPoints').textContent = variantCount > 1
            ? `${maxVariantScore} / вариант`
            : (currentTest.questions || []).reduce((sum, question) => sum + (question.points || 0), 0);
        document.getElementById('timeLimit').textContent = currentTest.time_limit ? `${currentTest.time_limit} мин` : 'Без лимита';
        document.getElementById('testStatus').textContent = getTestStatusLabel(currentTest);
        document.getElementById('variantCount').textContent = variantCount;
        document.getElementById('deliveryMode').textContent = getDeliveryModeLabel(currentTest.delivery_mode);

        const questionsList = document.getElementById('questionsList');
        questionsList.innerHTML = buildQuestionCards(variantSummaries);

        const criteria = [...(currentTest.grade_criteria || [])].sort((a, b) => b.min_points - a.min_points);
        document.getElementById('gradeCriteriaList').innerHTML = criteria.map((criterion) => `
            <div class="flex justify-between items-center bg-slate-50 rounded-2xl px-4 py-3 dark:bg-slate-950/70">
                <span class="font-medium text-slate-800 dark:text-slate-100">${escapeHtml(criterion.label)}</span>
                <span class="text-slate-600 dark:text-slate-400">от <span class="font-semibold text-slate-900 dark:text-white">${criterion.min_points}</span> балл.</span>
            </div>
        `).join('');

        const hasTooManyAnswers = (currentTest.questions || []).some((question) => (question.answers || []).length > 5);
        const answerSheetPageCount = Math.max(
            1,
            ...variantSummaries.map((summary) => Math.max(1, Math.ceil(summary.questionCount / SCAN_ROWS_PER_PAGE)))
        );
        syncScanControlsState({
            variantSummaries,
            hasTooManyAnswers,
            answerSheetPageCount,
        });

        if (normalizeVariantCount() <= 1) {
            blankVariantDistributionMode = 'same';
            sharedVariantNumber = 1;
            customStudentVariants = {};
        } else {
            sharedVariantNumber = clampVariantNumber(sharedVariantNumber);
        }

        renderVariantAssignmentSettings();
        renderElectronicVariantOptions();
        syncTestAvailabilityUi();
    }

    function buildQuestionCards(variantSummaries) {
        return variantSummaries.map((summary) => `
            <section class="space-y-4">
                ${normalizeVariantCount() > 1 ? `
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600 dark:bg-slate-950 dark:border-slate-700 dark:text-slate-300">
                        <span class="font-semibold text-slate-900 dark:text-white">Вариант ${summary.variantNumber}</span>
                        <span class="ml-3">Вопросов: ${summary.questionCount}</span>
                        <span class="ml-3">Макс. балл: ${summary.score}</span>
                    </div>
                ` : ''}
                ${summary.questions.map((questionData) => `
                    <article class="border border-slate-200 rounded-2xl p-4 dark:border-slate-700 ${questionData.question.type === 'multiple' ? 'bg-violet-50 dark:bg-violet-950/20' : 'bg-slate-50 dark:bg-slate-950/70'}">
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-white">${questionData.number}. ${escapeHtml(questionData.question.question_text)}</h3>
                                    ${normalizeVariantCount() > 1 ? `
                                        <span class="bg-white border border-slate-200 rounded-full px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200">
                                            Вариант ${summary.variantNumber}
                                        </span>
                                    ` : ''}
                                </div>
                                <div class="text-sm text-slate-500 mt-2 dark:text-slate-400">
                                    ${questionData.question.type === 'single' ? 'Один правильный ответ' : 'Несколько правильных ответов'}
                                </div>
                            </div>
                            <span class="inline-flex shrink-0 whitespace-nowrap bg-white border border-slate-200 rounded-full px-3 py-1 text-sm font-semibold text-slate-700 dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200">
                                ${formatPointsLabel(questionData.question.points || 1)}
                            </span>
                        </div>

                        <div class="mt-4 grid gap-2">
                            ${(questionData.question.answers || []).map((answer, answerIndex) => `
                                <div class="flex items-start gap-3 rounded-xl px-3 py-2 ${answer.is_correct ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-500/15 dark:text-emerald-100' : 'bg-white text-slate-800 dark:bg-slate-900 dark:text-slate-100'}">
                                    <span class="font-semibold">${String.fromCharCode(65 + answerIndex)}.</span>
                                    <span>${escapeHtml(answer.answer_text)}</span>
                                    ${answer.is_correct ? '<i class="fas fa-check mt-1 text-emerald-700 dark:text-emerald-300"></i>' : ''}
                                </div>
                            `).join('')}
                        </div>
                    </article>
                `).join('')}
            </section>
        `).join('');
    }

    function normalizeVariantCount() {
        return Math.max(1, Math.min(10, Number(currentTest?.variant_count) || 1));
    }

    function formatPointsLabel(points) {
        const value = Math.max(0, Number(points) || 0);
        const mod10 = value % 10;
        const mod100 = value % 100;

        if (mod10 === 1 && mod100 !== 11) {
            return `${value} балл`;
        }

        if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
            return `${value} балла`;
        }

        return `${value} баллов`;
    }

    function getAvailableVariantNumbers() {
        return Array.from({ length: normalizeVariantCount() }, (_, index) => index + 1);
    }

    function clampVariantNumber(value) {
        const normalized = Number(value) || 1;

        return Math.max(1, Math.min(normalizeVariantCount(), normalized));
    }

    function getQuestionVariantNumber(question) {
        return clampVariantNumber(question?.variant_number || 1);
    }

    function getVariantSummaries() {
        const variantCount = normalizeVariantCount();
        const questions = Array.isArray(currentTest?.questions) ? currentTest.questions : [];

        return getAvailableVariantNumbers().map((variantNumber) => {
            const variantQuestions = questions
                .filter((question) => getQuestionVariantNumber(question) === variantNumber)
                .sort((left, right) => (left.order || 0) - (right.order || 0))
                .map((question, index) => ({
                    number: index + 1,
                    question,
                }));

            return {
                variantNumber,
                questions: variantQuestions,
                questionCount: variantQuestions.length,
                score: variantQuestions.reduce((sum, questionData) => sum + (questionData.question.points || 0), 0),
            };
        }).filter((summary) => summary.questionCount > 0 || variantCount === 1);
    }

    function renderVariantAssignmentSettings() {
        const container = document.getElementById('variantAssignmentSettings');
        const summary = document.getElementById('variantAssignmentSummary');
        const variantCount = normalizeVariantCount();

        if (!container || !summary) {
            return;
        }

        if (variantCount <= 1) {
            summary.textContent = 'Для этого теста используется один вариант.';
            container.innerHTML = `
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                    Дополнительные настройки вариантов не нужны: у теста только один вариант.
                </div>
            `;
            return;
        }

        const buttons = [
            {
                id: 'same',
                title: 'Всем один вариант',
                description: 'Каждому выбранному ученику будет выдан один и тот же вариант.'
            },
            {
                id: 'balanced',
                title: 'Распределить поровну',
                description: 'Варианты будут выданы по кругу, чтобы группа делилась максимально равномерно.'
            },
            {
                id: 'custom',
                title: 'Назначить вручную',
                description: 'Для каждого студента можно выбрать свой вариант прямо в списке группы.'
            }
        ];

        summary.textContent = blankVariantDistributionMode === 'balanced'
            ? `Сейчас варианты 1-${variantCount} будут распределяться по выбранным студентам максимально равномерно.`
            : blankVariantDistributionMode === 'custom'
                ? 'Сейчас можно вручную назначить вариант каждому студенту в списке ниже.'
                : `Сейчас всем выбранным студентам будет выдан вариант ${clampVariantNumber(sharedVariantNumber)}.`;

        container.innerHTML = `
            <div class="grid md:grid-cols-3 gap-3">
                ${buttons.map((button) => `
                    <button type="button"
                            onclick="setVariantDistributionMode('${button.id}')"
                            class="${blankVariantDistributionMode === button.id
                                ? 'rounded-2xl border border-sky-600 bg-sky-600 px-4 py-3 text-left transition text-white'
                                : 'rounded-2xl border border-slate-300 px-4 py-3 text-left transition hover:border-sky-300 hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:bg-slate-900'}">
                        <div class="font-semibold">${button.title}</div>
                        <div class="text-sm mt-1 opacity-80">${button.description}</div>
                    </button>
                `).join('')}
            </div>
            ${blankVariantDistributionMode === 'same' ? `
                <div>
                    <label for="sharedVariantNumber" class="block text-sm font-medium text-slate-700 mb-2 dark:text-slate-300">Общий вариант для всех выбранных</label>
                    <select id="sharedVariantNumber"
                            onchange="updateSharedVariantNumber(this.value)"
                            class="w-full md:max-w-xs px-4 py-3 rounded-2xl border border-slate-300 focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        ${getAvailableVariantNumbers().map((variantNumber) => `
                            <option value="${variantNumber}" ${variantNumber === clampVariantNumber(sharedVariantNumber) ? 'selected' : ''}>
                                Вариант ${variantNumber}
                            </option>
                        `).join('')}
                    </select>
                </div>
            ` : ''}
        `;
    }

    function setVariantDistributionMode(mode) {
        if (normalizeVariantCount() <= 1) {
            blankVariantDistributionMode = 'same';
            renderVariantAssignmentSettings();
            return;
        }

        blankVariantDistributionMode = ['same', 'balanced', 'custom'].includes(mode) ? mode : 'same';

        if (blankVariantDistributionMode !== 'custom') {
            customStudentVariants = {};
        }

        renderVariantAssignmentSettings();
        renderGroupStudents();
    }

    function updateSharedVariantNumber(value) {
        sharedVariantNumber = clampVariantNumber(value);
        renderVariantAssignmentSettings();
        renderGroupStudents();
    }

    function renderGroups() {
        const select = document.getElementById('groupSelect');

        if (!groups.length) {
            select.innerHTML = '<option value="">Сначала создайте учебную группу</option>';
            renderGroupStudents();
            return;
        }

        select.innerHTML = `
            <option value="">Выберите группу</option>
            ${groups.map((group) => `<option value="${group.id}">${escapeHtml(group.name)} (${group.students?.length || 0})</option>`).join('')}
        `;

        renderGroupStudents();
    }

    function renderElectronicGroupSelect() {
        const select = document.getElementById('electronicGroupSelect');
        if (!select) {
            return;
        }

        if (!groups.length) {
            select.innerHTML = '<option value="">Сначала создайте учебную группу</option>';
            return;
        }

        select.innerHTML = `
            <option value="">Выберите группу</option>
            ${groups.map((group) => `<option value="${group.id}">${escapeHtml(group.name)} (${group.students?.length || 0})</option>`).join('')}
        `;
    }

    function renderElectronicVariantOptions() {
        const select = document.getElementById('electronicVariantNumber');
        if (!select) {
            return;
        }

        const variantCount = normalizeVariantCount();
        select.innerHTML = Array.from({ length: variantCount }, (_, index) => {
            const value = index + 1;
            return `<option value="${value}">Вариант ${value}</option>`;
        }).join('');
    }

    function getDeliveryModeLabel(mode) {
        switch (mode) {
            case 'electronic':
                return 'Электронный';
            case 'hybrid':
                return 'Совмещённый';
            default:
                return 'На бланках';
        }
    }

    async function loadElectronicDashboard() {
        const response = await apiFetch(`/api/tests/${testId}/electronic-dashboard`);
        if (!response.ok) {
            const payload = await response.json().catch(() => ({}));
            const message = payload.errors
                ? Object.values(payload.errors).flat().join(', ')
                : (payload.message || 'Не удалось загрузить электронное тестирование');
            throw new Error(message);
        }

        const payload = await response.json();
        const previousSubmittedIds = (electronicDashboard?.current_session?.attempts || [])
            .filter((attempt) => ['submitted', 'reviewed'].includes(attempt.status))
            .map((attempt) => attempt.id);

        electronicDashboard = payload.data || {};
        renderElectronicDashboard();
        notifyAboutNewElectronicAttempts(previousSubmittedIds);
        startElectronicPolling();
    }

    function renderElectronicDashboard() {
        const accessCode = document.getElementById('electronicAccessCode');
        const generalLink = document.getElementById('electronicGeneralLink');
        const membersList = document.getElementById('electronicMembersList');
        const attemptsList = document.getElementById('electronicAttemptsList');
        const attemptsSummary = document.getElementById('electronicAttemptsSummary');
        const badge = document.getElementById('electronicLiveBadge');
        const sameVariantWrap = document.getElementById('electronicSameVariantWrap');
        const isBlankMode = (electronicDashboard?.delivery_mode || currentTest?.delivery_mode) === 'blank';
        const isClosed = isCurrentTestClosed();

        accessCode.textContent = electronicDashboard?.access_code || '—';
        generalLink.textContent = electronicDashboard?.current_session?.general_link || 'Сначала запустите тест';
        generalLink.href = electronicDashboard?.current_session?.general_link || '#';
        generalLink.classList.toggle('pointer-events-none', !electronicDashboard?.current_session?.general_link);
        sameVariantWrap.classList.toggle('hidden', document.getElementById('electronicVariantMode').value !== 'same');

        if (isBlankMode) {
            badge.classList.add('hidden');
            membersList.innerHTML = `
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                    Этот тест пока переведён только в режим бланков.
                </div>
            `;
            attemptsSummary.textContent = 'Электронный режим выключен';
            attemptsList.innerHTML = '';
            return;
        }

        const currentSession = electronicDashboard?.current_session;
        if (!currentSession) {
            badge.classList.add('hidden');
            membersList.innerHTML = `
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                    ${isClosed ? 'Тест закрыт. Новый электронный запуск недоступен.' : 'Активного запуска пока нет. Выберите группу и создайте новый запуск.'}
                </div>
            `;
            attemptsSummary.textContent = isClosed ? 'Тест закрыт' : 'Нет активного запуска';
            attemptsList.innerHTML = '';
            syncTestAvailabilityUi();
            return;
        }

        badge.classList.remove('hidden');
        badge.textContent = `${currentSession.group?.name || 'Группа'} • ${currentSession.unreviewed_count || 0} новых работ`;

        membersList.innerHTML = (currentSession.members || []).length
            ? currentSession.members.map((member) => `
                <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">${escapeHtml(member.full_name || 'Ученик')}</div>
                            <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">Вариант ${member.variant_number}</div>
                        </div>
                        <button type="button" onclick="copyTextToClipboard('${member.personal_link}')" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                            Копировать ссылку
                        </button>
                    </div>
                </article>
            `).join('')
            : '<div class="text-sm text-slate-500 dark:text-slate-400">В выбранной группе пока нет учеников.</div>';

        const attempts = currentSession.attempts || [];
        attemptsSummary.textContent = attempts.length
            ? `Работ: ${attempts.length}. Новых без оценки: ${currentSession.unreviewed_count || 0}`
            : 'Пока нет завершённых работ';
        attemptsList.innerHTML = attempts.length
            ? attempts.map((attempt) => buildElectronicAttemptCard(attempt)).join('')
            : '<div class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-4 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">Завершённых работ пока нет.</div>';
        syncTestAvailabilityUi();
    }

    function buildElectronicAttemptCard(attempt) {
        return `
            <article class="rounded-2xl border border-slate-200 bg-white px-4 py-4 dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="font-semibold text-slate-900 dark:text-white">${escapeHtml(attempt.student_full_name || 'Ученик')}</div>
                        <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            ${attempt.submitted_at ? `Сдан: ${formatDate(attempt.submitted_at)}` : 'В процессе'} • Вариант ${attempt.variant_number}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-semibold ${attempt.status === 'reviewed' ? 'text-emerald-600 dark:text-emerald-300' : 'text-indigo-600 dark:text-indigo-300'}">${escapeHtml(attempt.status_label || 'Работа')}</div>
                        <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">${attempt.total_score ?? '—'} балл.</div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2 text-sm">
                    <div class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-950/70">
                        Автооценка: <span class="font-semibold text-slate-900 dark:text-white">${escapeHtml(attempt.grade_label || 'Без оценки')}</span>
                    </div>
                    <div class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-950/70">
                        Оценка в журнал: <span class="font-semibold text-slate-900 dark:text-white">${escapeHtml(attempt.assigned_grade_value || 'не выставлена')}</span>
                    </div>
                    ${attempt.is_manual_student ? `
                        <div class="rounded-full bg-amber-100 px-3 py-1 text-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                            Ученик вне списка группы
                        </div>
                    ` : ''}
                </div>

                ${(attempt.log_summary_items || []).length ? `
                    <div class="mt-4 flex flex-wrap gap-2 text-xs">
                        ${(attempt.log_summary_items || []).map((item) => `
                            <div class="rounded-full border border-slate-200 px-3 py-1 text-slate-600 dark:border-slate-700 dark:text-slate-300">
                                ${escapeHtml(item.label)}: <span class="font-semibold text-slate-900 dark:text-white">${item.count}</span>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}

                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="/electronic-attempts/${attempt.id}" class="rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-indigo-500">
                        Открыть проверку
                    </a>
                </div>
            </article>
        `;
    }

    function startElectronicPolling() {
        clearInterval(electronicPollHandle);
        electronicPollHandle = setInterval(async () => {
            if (document.hidden || !electronicDashboard || currentTest?.delivery_mode === 'blank' || isCurrentTestClosed()) {
                return;
            }

            try {
                const response = await apiFetch(`/api/tests/${testId}/electronic-dashboard`);
                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                const previousSubmittedIds = (electronicDashboard?.current_session?.attempts || [])
                    .filter((attempt) => ['submitted', 'reviewed'].includes(attempt.status))
                    .map((attempt) => attempt.id);
                electronicDashboard = payload.data || {};
                renderElectronicDashboard();
                notifyAboutNewElectronicAttempts(previousSubmittedIds);
            } catch (error) {
                console.error('Ошибка обновления электронного тестирования:', error);
            }
        }, 15000);
    }

    function notifyAboutNewElectronicAttempts(previousSubmittedIds = []) {
        const currentSubmittedAttempts = (electronicDashboard?.current_session?.attempts || [])
            .filter((attempt) => ['submitted', 'reviewed'].includes(attempt.status));
        const newAttempts = currentSubmittedAttempts.filter((attempt) => !previousSubmittedIds.includes(attempt.id));

        if (!newAttempts.length) {
            electronicKnownSubmittedAttemptIds = currentSubmittedAttempts.map((attempt) => attempt.id);
            return;
        }

        if (window.Notification && Notification.permission === 'default') {
            Notification.requestPermission().catch(() => {});
        }

        newAttempts.forEach((attempt) => {
            if (window.Notification && Notification.permission === 'granted') {
                new Notification('Провериум: новая завершённая работа', {
                    body: `${attempt.student_full_name} завершил(а) тест.`,
                });
            }
        });

        electronicKnownSubmittedAttemptIds = currentSubmittedAttempts.map((attempt) => attempt.id);
    }

    async function startElectronicSession() {
        if (isCurrentTestClosed()) {
            alert('Тест закрыт. Новые электронные прохождения для него недоступны.');
            return;
        }

        if (currentTest?.delivery_mode === 'blank') {
            const shouldEnableHybrid = confirm('Этот тест сейчас работает только на бланках. Переключить формат на совмещённый и продолжить запуск электронного теста?');
            if (!shouldEnableHybrid) {
                return;
            }

            try {
                await updateTestDeliveryMode('hybrid');
            } catch (error) {
                alert(error.message || 'Не удалось переключить формат теста.');
                return;
            }
        }
        const studentGroupId = parseInt(document.getElementById('electronicGroupSelect').value, 10);
        if (!studentGroupId) {
            alert('Выберите группу для электронного тестирования.');
            return;
        }

        const variantAssignmentMode = document.getElementById('electronicVariantMode').value;
        const payload = {
            student_group_id: studentGroupId,
            variant_assignment_mode: variantAssignmentMode,
        };

        if (variantAssignmentMode === 'same') {
            payload.variant_number = parseInt(document.getElementById('electronicVariantNumber').value, 10) || 1;
        }

        try {
            const response = await apiFetch(`/api/tests/${testId}/electronic-launch`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                const message = error.errors ? Object.values(error.errors).flat().join(', ') : (error.message || 'Не удалось создать электронный запуск.');
                throw new Error(message);
            }

            await loadElectronicDashboard();
            alert('Электронный запуск создан. Можно раздавать ссылку или код.');
        } catch (error) {
            alert(error.message || 'Ошибка запуска');
        }
    }

    async function assignElectronicGrade(attemptId) {
        const gradeValue = document.getElementById(`attemptGrade_${attemptId}`)?.value.trim();
        const gradeDate = document.getElementById(`attemptGradeDate_${attemptId}`)?.value;

        if (!gradeValue || !gradeDate) {
            alert('Укажите оценку и дату.');
            return;
        }

        try {
            const response = await apiFetch(`/api/electronic-attempts/${attemptId}/assign-grade`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    grade_value: gradeValue,
                    grade_date: gradeDate,
                }),
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.errors ? Object.values(error.errors).flat().join(', ') : (error.message || 'Не удалось сохранить оценку.'));
            }

            await loadElectronicDashboard();
        } catch (error) {
            alert(error.message || 'Ошибка сохранения оценки');
        }
    }

    async function attachElectronicStudent(attemptId) {
        const studentFullName = document.getElementById(`attachStudentName_${attemptId}`)?.value.trim();
        const gradeValue = document.getElementById(`attemptGrade_${attemptId}`)?.value.trim();
        const gradeDate = document.getElementById(`attemptGradeDate_${attemptId}`)?.value;

        if (!studentFullName) {
            alert('Укажите ФИО ученика.');
            return;
        }

        try {
            const response = await apiFetch(`/api/electronic-attempts/${attemptId}/attach-student`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_full_name: studentFullName,
                    grade_value: gradeValue || null,
                    grade_date: gradeDate || null,
                }),
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.errors ? Object.values(error.errors).flat().join(', ') : (error.message || 'Не удалось привязать ученика.'));
            }

            await loadElectronicDashboard();
        } catch (error) {
            alert(error.message || 'Ошибка привязки ученика');
        }
    }

    async function copyElectronicCode() {
        if (!electronicDashboard?.access_code) {
            alert('Код пока не готов.');
            return;
        }

        await copyTextToClipboard(electronicDashboard.access_code);
    }

    async function copyTextToClipboard(value) {
        try {
            await navigator.clipboard.writeText(value);
            alert('Скопировано.');
        } catch (error) {
            alert('Не удалось скопировать автоматически. Скопируйте вручную: ' + value);
        }
    }

    function getSelectedGroup() {
        const groupId = Number(document.getElementById('groupSelect').value);
        if (!Number.isInteger(groupId) || groupId <= 0) {
            return null;
        }

        return groups.find((group) => Number(group.id) === groupId) || null;
    }

    function getSelectedGroupStudents() {
        return getSelectedGroup()?.students || [];
    }

    function getAllSelectedGroupStudentIds() {
        return getSelectedGroupStudents()
            .map((student) => Number(student.id))
            .filter((value) => Number.isInteger(value) && value > 0);
    }

    function normalizeBufferedSelectedGroupStudentIds() {
        const allowedIds = new Set(getAllSelectedGroupStudentIds());

        return [...new Set(selectedGroupStudentIds
            .map((value) => Number(value))
            .filter((value) => allowedIds.has(value)))];
    }

    function getRenderableSelectedGroupStudentIds() {
        return blankGenerationMode === 'all'
            ? getAllSelectedGroupStudentIds()
            : normalizeBufferedSelectedGroupStudentIds();
    }

    function getStudentsForGeneration() {
        const students = getSelectedGroupStudents();
        const selectedIds = new Set(getRenderableSelectedGroupStudentIds());

        return blankGenerationMode === 'all'
            ? students
            : students.filter((student) => selectedIds.has(Number(student.id)));
    }

    function getStudentVariantAssignments() {
        const variantCount = normalizeVariantCount();
        const students = getSelectedGroupStudents();
        const selectedIds = new Set(getRenderableSelectedGroupStudentIds());
        const assignments = {};
        let balancedIndex = 0;

        students.forEach((student) => {
            const studentId = Number(student.id);
            const isIncluded = blankGenerationMode === 'all' || selectedIds.has(studentId);

            if (variantCount <= 1) {
                assignments[studentId] = 1;
                return;
            }

            if (blankVariantDistributionMode === 'balanced' && isIncluded) {
                assignments[studentId] = (balancedIndex % variantCount) + 1;
                balancedIndex += 1;
                return;
            }

            if (blankVariantDistributionMode === 'custom') {
                assignments[studentId] = clampVariantNumber(customStudentVariants[studentId] || 1);
                return;
            }

            assignments[studentId] = clampVariantNumber(sharedVariantNumber);
        });

        return assignments;
    }

    function handleGroupChange() {
        selectedGroupStudentIds = [];
        blankGenerationMode = 'all';
        customStudentVariants = {};
        syncBlankGenerationModeButtons();
        renderVariantAssignmentSettings();
        renderGroupStudents();
    }

    function setBlankGenerationMode(mode) {
        blankGenerationMode = mode === 'selected' ? 'selected' : 'all';
        if (blankGenerationMode === 'all') {
            selectedGroupStudentIds = [];
        } else {
            selectedGroupStudentIds = normalizeBufferedSelectedGroupStudentIds();
        }
        syncBlankGenerationModeButtons();
        renderVariantAssignmentSettings();
        renderGroupStudents();
    }

    function updateCustomStudentVariant(studentId, variantNumber) {
        customStudentVariants = {
            ...customStudentVariants,
            [Number(studentId)]: clampVariantNumber(variantNumber),
        };
        blankVariantDistributionMode = 'custom';
        renderVariantAssignmentSettings();
        renderGroupStudents();
    }

    function syncBlankGenerationModeButtons() {
        const allButton = document.getElementById('generateModeAllButton');
        const selectedButton = document.getElementById('generateModeSelectedButton');
        const isAllMode = blankGenerationMode === 'all';

        if (!allButton || !selectedButton) {
            return;
        }

        allButton.className = isAllMode
            ? 'rounded-2xl border border-sky-600 bg-sky-600 px-4 py-3 text-left transition text-white'
            : 'rounded-2xl border border-slate-300 px-4 py-3 text-left transition hover:border-sky-300 hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:bg-slate-900';
        selectedButton.className = !isAllMode
            ? 'rounded-2xl border border-sky-600 bg-sky-600 px-4 py-3 text-left transition text-white'
            : 'rounded-2xl border border-slate-300 px-4 py-3 text-left transition hover:border-sky-300 hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:bg-slate-900';
    }

    function renderGroupStudents() {
        const list = document.getElementById('groupStudentsList');
        const summary = document.getElementById('studentSelectionSummary');
        const selectAllButton = document.getElementById('selectAllStudentsButton');
        const clearButton = document.getElementById('clearStudentsButton');
        const group = getSelectedGroup();
        const students = group?.students || [];
        const selectedIds = new Set(getRenderableSelectedGroupStudentIds());
        const variantAssignments = getStudentVariantAssignments();
        const variantCount = normalizeVariantCount();

        if (!list || !summary || !selectAllButton || !clearButton) {
            return;
        }

        if (!group) {
            summary.textContent = 'Сначала выберите группу.';
            list.innerHTML = `
                <div class="sm:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                    После выбора группы здесь появится список ее студентов.
                </div>
            `;
            setStudentSelectionButtonsState(true);
            return;
        }

        if (!students.length) {
            summary.textContent = 'В этой группе пока нет студентов.';
            list.innerHTML = `
                <div class="sm:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                    Добавьте студентов в группу, и после этого можно будет выпускать для них бланки.
                </div>
            `;
            setStudentSelectionButtonsState(true);
            return;
        }

        setStudentSelectionButtonsState(false);

        const selectedCount = students.filter((student) => selectedIds.has(Number(student.id))).length;
        summary.textContent = blankGenerationMode === 'selected'
            ? `Выбрано ${selectedCount} из ${students.length}. Будут выпущены бланки только для отмеченных студентов.`
            : `Сейчас будет выпущена вся группа: ${students.length} студент(ов). При необходимости переключитесь на выборочный режим.`;

        list.innerHTML = students.map((student) => {
            const studentId = Number(student.id);
            const isChecked = selectedIds.has(studentId);
            const assignedVariant = variantAssignments[studentId] || 1;
            const variantBadge = variantCount > 1
                ? (blankVariantDistributionMode === 'custom'
                    ? `
                        <div class="mt-3">
                            <label class="block text-xs uppercase tracking-[0.2em] text-slate-400 mb-2 dark:text-slate-500">Вариант</label>
                            <select onchange="updateCustomStudentVariant(${studentId}, this.value)"
                                    class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-sky-500 focus:border-sky-500 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                                ${getAvailableVariantNumbers().map((variantNumber) => `
                                    <option value="${variantNumber}" ${variantNumber === assignedVariant ? 'selected' : ''}>
                                        Вариант ${variantNumber}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    `
                    : `
                        <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            <i class="fas fa-layer-group"></i>
                            ${isChecked ? `Вариант ${assignedVariant}` : 'Не выбран для печати'}
                        </div>
                    `)
                : '';

            return `
                <label class="flex items-start gap-3 rounded-2xl border px-4 py-3 cursor-pointer transition ${isChecked ? 'border-sky-300 bg-white shadow-sm dark:border-sky-500/60 dark:bg-slate-900 dark:shadow-none' : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-slate-600'}">
                    <input type="checkbox"
                           ${isChecked ? 'checked' : ''}
                           onchange="toggleGroupStudent(${studentId}, this.checked)"
                           class="mt-1 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                    <div class="min-w-0">
                        <div class="font-medium text-slate-900 dark:text-white">${escapeHtml(student.full_name || 'Без имени')}</div>
                        ${variantBadge}
                    </div>
                </label>
            `;
        }).join('');
    }

    function setStudentSelectionButtonsState(disabled) {
        const buttons = [
            document.getElementById('selectAllStudentsButton'),
            document.getElementById('clearStudentsButton')
        ].filter(Boolean);

        buttons.forEach((button) => {
            button.disabled = disabled;
            button.classList.toggle('opacity-50', disabled);
            button.classList.toggle('cursor-not-allowed', disabled);
        });
    }

    function toggleGroupStudent(studentId, checked) {
        const allIds = getAllSelectedGroupStudentIds();
        const nextIds = new Set(getRenderableSelectedGroupStudentIds());

        if (checked) {
            nextIds.add(Number(studentId));
        } else {
            nextIds.delete(Number(studentId));
        }

        const nextSelectedIds = Array.from(nextIds).filter((value) => allIds.includes(value));
        if (allIds.length && nextSelectedIds.length === allIds.length) {
            blankGenerationMode = 'all';
            selectedGroupStudentIds = [];
        } else {
            blankGenerationMode = 'selected';
            selectedGroupStudentIds = nextSelectedIds;
        }

        syncBlankGenerationModeButtons();
        renderVariantAssignmentSettings();
        renderGroupStudents();
    }

    function selectAllGroupStudents() {
        blankGenerationMode = 'all';
        syncBlankGenerationModeButtons();
        selectedGroupStudentIds = [];
        renderVariantAssignmentSettings();
        renderGroupStudents();
    }

    function clearSelectedGroupStudents() {
        blankGenerationMode = 'selected';
        syncBlankGenerationModeButtons();
        selectedGroupStudentIds = [];
        renderVariantAssignmentSettings();
        renderGroupStudents();
    }

    async function loadBlankForms() {
        const response = await apiFetch(`/api/blank-forms?test_id=${testId}&per_page=100`);
        if (!response.ok) {
            throw new Error('Не удалось загрузить бланки');
        }

        const data = await response.json();
        blankForms = data.data?.data || data.data || [];
        renderBlankForms();
    }

    function renderBlankForms() {
        const list = document.getElementById('blankFormsList');
        const bulkDeleteButton = document.getElementById('bulkDeleteBlankFormsButton');
        const deletableCount = blankForms.filter((blankForm) => ['generated', 'checked'].includes(blankForm.status)).length;

        if (bulkDeleteButton) {
            bulkDeleteButton.classList.toggle('hidden', deletableCount === 0);
            bulkDeleteButton.textContent = deletableCount > 0
                ? `Удалить все выпущенные (${deletableCount})`
                : 'Удалить все выпущенные';
        }

        if (!blankForms.length) {
            list.innerHTML = `
                <div class="text-center py-10 rounded-2xl border border-dashed border-slate-300 text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                    Для этого теста пока не выпущено ни одного персонального бланка.
                </div>
            `;
            return;
        }

        list.innerHTML = blankForms.map((blankForm) => {
            const statusMap = {
                generated: ['Сгенерирован', 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'],
                submitted: ['Загружен', 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'],
                checked: ['Проверен', 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300']
            };

            const [statusLabel, statusClass] = statusMap[blankForm.status] || ['Неизвестно', 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'];
            const studentName = [blankForm.last_name, blankForm.first_name, blankForm.patronymic].filter(Boolean).join(' ') || 'Без имени';
            const assignedGrade = blankForm.assigned_grade_value
                ? `${escapeHtml(blankForm.assigned_grade_value)} • ${formatDate(blankForm.assigned_grade_date)}`
                : '';
            const variantLabel = `Вариант ${blankForm.variant_number || 1}`;

            return `
                <article class="border border-slate-200 rounded-2xl p-4 dark:border-slate-700 dark:bg-slate-950/70">
                    <div class="flex flex-wrap justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h3 class="font-semibold text-slate-900 dark:text-white">${escapeHtml(studentName)}</h3>
                                <span class="px-3 py-1 rounded-full text-xs ${statusClass}">${statusLabel}</span>
                                <span class="px-3 py-1 rounded-full text-xs bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">${escapeHtml(variantLabel)}</span>
                            </div>
                            <div class="text-sm text-slate-500 mt-2 dark:text-slate-400">${escapeHtml(blankForm.group_name || 'Группа не указана')}</div>
                            <div class="text-xs text-slate-400 mt-2 dark:text-slate-500">${escapeHtml(blankForm.form_number || '')}</div>
                        </div>

                        <div class="text-right">
                            <div class="text-sm text-slate-500 dark:text-slate-400">Результат</div>
                            <div class="font-semibold text-slate-900 dark:text-white">${blankForm.total_score ?? '—'} ${blankForm.grade_label ? `• ${escapeHtml(blankForm.grade_label)}` : ''}</div>
                            ${assignedGrade ? `<div class="text-xs text-slate-500 mt-2 dark:text-slate-400">Поставленная оценка: ${assignedGrade}</div>` : ''}
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 mt-4">
                        <button onclick="printBlankForm(${blankForm.id})" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl hover:border-sky-300 hover:text-sky-700 transition text-sm dark:bg-slate-950 dark:border-slate-700 dark:text-slate-200 dark:hover:border-sky-400 dark:hover:text-sky-300">
                            Печать
                        </button>
                        ${blankForm.status === 'checked' ? `
                            <button onclick="openResultsPage([${blankForm.id}])" class="bg-sky-600 text-white px-4 py-2 rounded-xl hover:bg-sky-500 transition text-sm">
                                Разбор
                            </button>
                        ` : ''}
                        ${['generated', 'checked'].includes(blankForm.status) ? `
                            <button onclick="deleteBlankForm(${blankForm.id}, '${blankForm.status}')" class="bg-rose-600 text-white px-4 py-2 rounded-xl hover:bg-rose-500 transition text-sm">
                                Удалить
                            </button>
                        ` : ''}
                    </div>
                </article>
            `;
        }).join('');
    }

    async function deleteIssuedBlankForms() {
        const deletableCount = blankForms.filter((blankForm) => ['generated', 'checked'].includes(blankForm.status)).length;

        if (!deletableCount) {
            alert('Для этого теста нет выпущенных бланков, доступных для удаления.');
            return;
        }

        if (!confirm(`Удалить все выпущенные бланки (${deletableCount})? Это действие нельзя отменить.`)) {
            return;
        }

        try {
            const response = await apiFetch(`/api/tests/${testId}/blank-forms`, {
                method: 'DELETE'
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || 'Не удалось удалить выпущенные бланки');
            }

            lastGeneratedBlankIds = [];
            document.getElementById('printGeneratedActions').classList.add('hidden');
            await loadBlankForms();
        } catch (error) {
            alert(error.message || 'Ошибка удаления');
        }
    }

    async function generateBlankForms() {
        if (isCurrentTestClosed()) {
            alert('Тест закрыт. Новые бланки для него больше не выпускаются.');
            return;
        }

        if (currentTest?.delivery_mode === 'electronic') {
            const shouldEnableHybrid = confirm('Этот тест сейчас работает только в электронном режиме. Переключить формат на совмещённый и продолжить выпуск бланков?');
            if (!shouldEnableHybrid) {
                return;
            }

            try {
                await updateTestDeliveryMode('hybrid');
            } catch (error) {
                alert(error.message || 'Не удалось переключить формат теста.');
                return;
            }
        }

        const groupId = document.getElementById('groupSelect').value;
        if (!groupId) {
            alert('Выберите группу');
            return;
        }

        const students = getSelectedGroupStudents();
        if (!students.length) {
            alert('В выбранной группе пока нет студентов');
            return;
        }

        const selectedIds = normalizeBufferedSelectedGroupStudentIds();
        const selectedStudents = getStudentsForGeneration();
        const variantAssignments = getStudentVariantAssignments();

        if (blankGenerationMode === 'selected' && !selectedIds.length) {
            alert('Отметьте хотя бы одного студента для выборочной генерации');
            return;
        }

        try {
            const payload = {
                student_group_id: parseInt(groupId, 10),
                variant_assignment_mode: blankVariantDistributionMode,
            };

            if (blankVariantDistributionMode === 'same') {
                payload.variant_number = clampVariantNumber(sharedVariantNumber);
            } else if (blankVariantDistributionMode === 'custom') {
                payload.variant_numbers = selectedStudents.reduce((carry, student) => {
                    carry[student.id] = variantAssignments[Number(student.id)] || 1;

                    return carry;
                }, {});
            }

            if (blankGenerationMode === 'selected') {
                payload.group_student_ids = selectedIds;
            }

            const response = await apiFetch(`/api/tests/${testId}/generate-blank-forms`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Не удалось сгенерировать бланки');
            }

            const data = await response.json();
            lastGeneratedBlankIds = (data.data || []).map((item) => item.id);
            document.getElementById('printGeneratedActions').classList.toggle('hidden', !lastGeneratedBlankIds.length);
            await loadBlankForms();
            alert(blankGenerationMode === 'selected'
                ? `Сгенерировано бланков для выбранных студентов: ${lastGeneratedBlankIds.length}`
                : `Сгенерировано бланков: ${lastGeneratedBlankIds.length}`);
        } catch (error) {
            alert(error.message || 'Ошибка генерации');
        }
    }

    function openPrintPreview() {
        window.location.href = buildPrintUrl([]);
    }

    async function downloadTestExport(format) {
        try {
            const response = await apiFetch(`/api/tests/${testId}/export?format=${encodeURIComponent(format)}`, {
                headers: {
                    'Accept': format === 'xlsx'
                        ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        : 'application/json'
                }
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || 'Не удалось экспортировать тест');
            }

            const blob = await response.blob();
            const fileName = extractDownloadFileName(
                response.headers.get('content-disposition'),
                `test-${testId}.${format === 'xlsx' ? 'xlsx' : 'json'}`
            );

            triggerBlobDownload(blob, fileName);
        } catch (error) {
            alert(error.message || 'Ошибка экспорта');
        }
    }

    function printGeneratedPack() {
        if (!lastGeneratedBlankIds.length) {
            return;
        }

        window.location.href = buildPrintUrl(lastGeneratedBlankIds);
    }

    function printBlankForm(blankFormId) {
        window.location.href = buildPrintUrl([blankFormId]);
    }

    function buildPrintUrl(blankFormIds = []) {
        const params = new URLSearchParams();
        if (blankFormIds.length) {
            params.set('blank_form_ids', blankFormIds.join(','));
        }
        if (!blankFormIds.length && normalizeVariantCount() > 1) {
            params.set('variant_number', String(clampVariantNumber(sharedVariantNumber)));
        }

        const query = params.toString();
        return query ? `/tests/${testId}/print?${query}` : `/tests/${testId}/print`;
    }

    async function deleteBlankForm(blankFormId, status) {
        const actionLabel = status === 'checked' ? 'проверенную работу' : 'сгенерированный бланк';
        if (!confirm(`Удалить ${actionLabel}? Это действие нельзя отменить.`)) {
            return;
        }

        try {
            const response = await apiFetch(`/api/blank-forms/${blankFormId}`, {
                method: 'DELETE'
            });

            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || 'Не удалось удалить бланк');
            }

            lastGeneratedBlankIds = lastGeneratedBlankIds.filter((id) => id !== blankFormId);
            document.getElementById('printGeneratedActions').classList.toggle('hidden', !lastGeneratedBlankIds.length);
            await loadBlankForms();
        } catch (error) {
            alert(error.message || 'Ошибка удаления');
        }
    }

    function openResultsPage(blankFormIds, previewTokens = []) {
        const ids = [...new Set((blankFormIds || []).map((value) => Number(value)).filter((value) => Number.isInteger(value) && value > 0))];
        const tokens = [...new Set((previewTokens || []).map((value) => String(value || '').trim()).filter(Boolean))];

        if (!ids.length && !tokens.length) {
            return;
        }

        const params = new URLSearchParams({
            test_id: String(testId)
        });

        if (ids.length) {
            params.set('ids', ids.join(','));
        }

        if (tokens.length) {
            params.set('preview_tokens', tokens.join(','));
        }

        window.location.href = `/blank-forms/results?${params.toString()}`;
    }

    function scrollToTestSection(sectionId) {
        document.getElementById(sectionId)?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }

    async function uploadScans() {
        const input = document.getElementById('scanFiles');
        const scanButton = document.getElementById('scanButton');
        const scanSupportState = resolveScanSupportState();

        if (scanSupportState.disabled) {
            alert(scanSupportState.noteText || 'Сканирование бланков сейчас недоступно.');
            return;
        }

        if (!input.files.length) {
            alert('Выберите хотя бы один файл');
            return;
        }

        const originalFiles = Array.from(input.files);
        const formData = new FormData();

        try {
            scanButton.disabled = true;
            scanButton.classList.add('opacity-70', 'cursor-wait');
            scanButton.textContent = 'Обрабатываю...';
            const preparedFiles = await prepareScanFilesForUpload(originalFiles, scanButton);
            preparedFiles.forEach((file) => formData.append('scans[]', file));

            const response = await authApiFetch(`/api/tests/${testId}/scan-blank-forms`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Не удалось обработать сканы');
            }

            const data = await response.json();
            const results = data.data || [];
            const processedIds = [...new Set(results
                .filter((result) => Number.isInteger(Number(result.blank_form_id)) && result.status === 'checked')
                .map((result) => Number(result.blank_form_id))
                .filter((value) => value > 0))];
            const previewTokens = [...new Set(results
                .filter((result) => result.status === 'foreign_preview' && result.preview_token)
                .map((result) => String(result.preview_token)))];
            input.value = '';

            if ((processedIds.length + previewTokens.length) === results.length && (processedIds.length || previewTokens.length)) {
                openResultsPage(processedIds, previewTokens);
                return;
            }

            renderScanResults(results);
            await loadBlankForms();
        } catch (error) {
            alert(error.message || 'Ошибка загрузки сканов');
        } finally {
            scanButton.classList.remove('opacity-70', 'cursor-wait');
            scanButton.textContent = 'Обработать сканы';
            syncScanControlsState();
        }
    }

    async function prepareScanFilesForUpload(files, scanButton) {
        const preparedFiles = [];

        for (let index = 0; index < files.length; index += 1) {
            const file = files[index];
            if (isPdfFile(file)) {
                scanButton.textContent = `Готовлю PDF ${index + 1}/${files.length}...`;
                preparedFiles.push(...await convertPdfToImageFiles(file));
                continue;
            }

            preparedFiles.push(file);
        }

        return preparedFiles;
    }

    function isPdfFile(file) {
        return file.type === 'application/pdf' || /\.pdf$/i.test(file.name || '');
    }

    async function convertPdfToImageFiles(file) {
        const pdfjsLib = await ensurePdfJsLoaded();
        const buffer = await file.arrayBuffer();
        const loadingTask = pdfjsLib.getDocument({ data: buffer });
        const pdf = await loadingTask.promise;
        const files = [];

        try {
            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
                const page = await pdf.getPage(pageNumber);
                const viewport = page.getViewport({ scale: 2.2 });
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d', { alpha: false });

                if (!context) {
                    throw new Error('Не удалось подготовить холст для конвертации PDF.');
                }

                canvas.width = Math.ceil(viewport.width);
                canvas.height = Math.ceil(viewport.height);
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, canvas.width, canvas.height);

                await page.render({
                    canvasContext: context,
                    viewport,
                    background: 'rgb(255,255,255)',
                }).promise;

                const blob = await new Promise((resolve, reject) => {
                    canvas.toBlob((result) => {
                        if (result) {
                            resolve(result);
                            return;
                        }

                        reject(new Error(`Не удалось сохранить изображение листа ${pageNumber} из PDF.`));
                    }, 'image/jpeg', 0.92);
                });

                const targetName = (file.name || 'scan.pdf').replace(/\.pdf$/i, '') + `-page${pageNumber}.jpg`;
                files.push(new File([blob], targetName, {
                    type: 'image/jpeg',
                    lastModified: Date.now(),
                }));
            }

            return files;
        } finally {
            await pdf.destroy();
        }
    }

    async function ensurePdfJsLoaded() {
        if (window.__pdfjsLib) {
            return window.__pdfjsLib;
        }

        if (!pdfJsLoadingPromise) {
            pdfJsLoadingPromise = import('https://cdn.jsdelivr.net/npm/pdfjs-dist@5.4.624/build/pdf.min.mjs')
                .then((module) => {
                    const pdfjsLib = module.default || module;
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@5.4.624/build/pdf.worker.min.mjs';
                    window.__pdfjsLib = pdfjsLib;

                    return pdfjsLib;
                })
                .catch((error) => {
                    pdfJsLoadingPromise = null;
                    throw new Error('Не удалось загрузить PDF-конвертер в браузере. Проверьте подключение к интернету и попробуйте снова.');
                });
        }

        return pdfJsLoadingPromise;
    }

    function renderScanResults(results) {
        const container = document.getElementById('scanResults');

        if (!results.length) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = results.map((result) => `
            <article class="rounded-2xl border ${result.status === 'incomplete_scan' ? 'border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30' : result.status === 'foreign_preview' ? 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950/70' : 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/20'} p-4">
                <div class="flex flex-wrap justify-between gap-3">
                    <div>
                        <div class="font-semibold ${result.status === 'incomplete_scan' ? 'text-amber-900 dark:text-amber-100' : result.status === 'foreign_preview' ? 'text-slate-900 dark:text-white' : 'text-emerald-900 dark:text-emerald-200'}">${escapeHtml(result.student_name || 'Без имени')}</div>
                        <div class="text-sm ${result.status === 'incomplete_scan' ? 'text-amber-800 dark:text-amber-200' : result.status === 'foreign_preview' ? 'text-slate-700 dark:text-slate-300' : 'text-emerald-800 dark:text-emerald-300'} mt-1">${escapeHtml(result.file_name || '')}</div>
                        <div class="text-xs mt-2 ${result.status === 'incomplete_scan' ? 'text-amber-700 dark:text-amber-300' : result.status === 'foreign_preview' ? 'text-slate-600 dark:text-slate-400' : 'text-emerald-700 dark:text-emerald-300'}">
                            ${escapeHtml(result.variant_number ? `Вариант ${result.variant_number}` : '')}
                            ${result.expected_pages ? `${result.variant_number ? ' • ' : ''}Листы: ${(result.pages_processed || []).join(', ') || '—'} из ${result.expected_pages}` : ''}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-semibold ${result.status === 'incomplete_scan' ? 'text-amber-900 dark:text-amber-100' : result.status === 'foreign_preview' ? 'text-slate-900 dark:text-white' : 'text-emerald-900 dark:text-emerald-200'}">${result.score ?? '—'} / ${result.max_score ?? '—'}</div>
                        <div class="text-sm ${result.status === 'incomplete_scan' ? 'text-amber-800 dark:text-amber-200' : result.status === 'foreign_preview' ? 'text-slate-700 dark:text-slate-300' : 'text-emerald-800 dark:text-emerald-300'}">${escapeHtml(result.grade || (result.status === 'incomplete_scan' ? 'Нужно загрузить все листы' : result.status === 'foreign_preview' ? 'Чужой бланк • без оценки' : ''))}</div>
                    </div>
                </div>
                ${result.warnings?.length ? `
                    <div class="mt-3 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl p-3 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200">
                        ${result.warnings.map((warning) => escapeHtml(warning)).join('<br>')}
                    </div>
                ` : ''}
                ${result.preview_token ? `
                    <div class="mt-3 flex justify-end">
                        <button onclick="openResultsPage([], ['${result.preview_token}'])" class="bg-sky-600 text-white px-4 py-2 rounded-xl hover:bg-sky-500 transition text-sm">
                            Открыть OCR-разбор
                        </button>
                    </div>
                ` : ''}
            </article>
        `).join('');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function extractDownloadFileName(contentDisposition, fallback) {
        if (!contentDisposition) {
            return fallback;
        }

        const utfMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
        if (utfMatch?.[1]) {
            return decodeURIComponent(utfMatch[1]);
        }

        const plainMatch = contentDisposition.match(/filename=\"?([^\";]+)\"?/i);
        return plainMatch?.[1] || fallback;
    }

    function triggerBlobDownload(blob, fileName) {
        const objectUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = objectUrl;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(objectUrl);
    }

    function formatDate(value) {
        if (!value) {
            return '';
        }

        return new Date(value).toLocaleDateString('ru-RU');
    }

    document.addEventListener('DOMContentLoaded', async () => {
        if (!await ensureAuthenticatedPage()) {
            return;
        }

        document.getElementById('electronicVariantMode')?.addEventListener('change', () => {
            document.getElementById('electronicSameVariantWrap')?.classList.toggle(
                'hidden',
                document.getElementById('electronicVariantMode').value !== 'same'
            );
        });

        syncBlankGenerationModeButtons();
        loadPage();
    });
</script>
</body>
</html>
