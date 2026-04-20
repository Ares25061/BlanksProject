<!DOCTYPE html>
<html lang="ru">
<head>
    @include('layouts.head', ['title' => 'Платформа авто проверки работ'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">

@include('layouts.nav')

<main class="relative overflow-hidden">
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-44 top-8 h-[36rem] w-[46rem] -rotate-[34deg] rounded-[4rem] bg-white/18 dark:bg-indigo-300/[0.04]"></div>
        <div class="absolute -left-16 bottom-[-10rem] h-[26rem] w-[42rem] -rotate-[34deg] rounded-[4rem] bg-indigo-400/18 shadow-[0_40px_120px_-80px_rgba(79,70,229,0.7)] dark:bg-indigo-500/[0.16]"></div>
        <div class="absolute left-[24%] bottom-[-3rem] h-[14rem] w-[30rem] -rotate-[34deg] rounded-[3rem] bg-violet-300/16 dark:bg-violet-400/[0.14]"></div>
        <div class="absolute right-[-8rem] top-[12rem] h-[20rem] w-[26rem] -rotate-[30deg] rounded-[3.5rem] bg-sky-300/10 dark:bg-sky-400/[0.08]"></div>
        <div class="absolute right-[10%] bottom-[6rem] h-[8rem] w-[18rem] -rotate-[30deg] rounded-[2.5rem] bg-white/10 dark:bg-white/[0.03]"></div>
    </div>

    <section class="max-w-7xl mx-auto px-4 pt-10 pb-8 md:pt-16 md:pb-12">
        <div class="grid gap-8 lg:grid-cols-[1.02fr_0.98fr] lg:items-center">
            <div class="space-y-7">
                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50/90 px-4 py-2 text-sm font-medium text-sky-800 shadow-sm dark:border-indigo-900 dark:bg-slate-900/80 dark:text-indigo-200">
                    <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                    OCR, бланки, журнал и проверка в одном рабочем контуре
                </div>

                <div class="space-y-4">
                    <h1 class="max-w-3xl text-4xl font-black tracking-tight text-slate-950 dark:text-white sm:text-5xl md:text-6xl">
                        Провериум собирает тест, печатает бланк и сам проверяет работу по скану.
                    </h1>
                    <p class="max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                        Платформа для преподавателя, где можно быстро выпустить персональные бланки, распознать ответы через OCR,
                        разобрать даже чужой бланк по известному тесту и сразу сохранить результат в журнал группы.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="/user/register" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-halo transition hover:bg-indigo-500">
                        Начать работу
                    </a>
                    <a href="/user/login" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-800 transition hover:border-slate-400 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                        Войти в кабинет
                    </a>
                    <a href="/tests" class="nav-link inline-flex items-center justify-center rounded-2xl border border-indigo-200 bg-indigo-50 px-6 py-3 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-950/60">
                        Открыть тесты
                    </a>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <article class="proverium-panel rounded-3xl p-5">
                        <div class="text-3xl font-black text-slate-950 dark:text-white">A4</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Бланк размечен под печать и автосканирование без ручной правки макета.
                        </p>
                    </article>
                    <article class="proverium-panel rounded-3xl p-5">
                        <div class="text-3xl font-black text-slate-950 dark:text-white">OCR</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Paddle OCR распознаёт ответы, а система собирает итог по страницам.
                        </p>
                    </article>
                    <article class="proverium-panel rounded-3xl p-5">
                        <div class="text-3xl font-black text-slate-950 dark:text-white">XLSX</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Импорт и экспорт вопросов, а также выгрузка журнала по группе за месяц.
                        </p>
                    </article>
                </div>
            </div>

            <div class="relative">
                <section class="proverium-panel relative rounded-[2rem] border border-white/70 p-6 md:p-8 dark:border-slate-800/80">
                    <div class="rounded-[1.75rem] bg-[linear-gradient(135deg,_rgba(255,255,255,0.78)_0%,_rgba(255,255,255,0.78)_26%,_rgba(99,102,241,0.18)_26%,_rgba(99,102,241,0.18)_60%,_rgba(96,165,250,0.11)_60%,_rgba(96,165,250,0.11)_100%)] p-5 dark:bg-[linear-gradient(135deg,_rgba(255,255,255,0.04)_0%,_rgba(255,255,255,0.04)_18%,_rgba(79,70,229,0.28)_18%,_rgba(79,70,229,0.28)_58%,_rgba(59,130,246,0.16)_58%,_rgba(59,130,246,0.16)_100%)]">
                        <img src="{{ asset('brand/proverium-logo-light.svg') }}"
                             alt="Провериум"
                             class="w-full dark:hidden">
                        <img src="{{ asset('brand/proverium-logo-dark.svg') }}"
                             alt="Провериум"
                             class="hidden w-full dark:block">
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <article class="rounded-3xl border border-indigo-100 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/85 dark:shadow-none">
                            <div class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-400 dark:text-slate-500">Поток работы</div>
                            <div class="mt-3 text-lg font-bold text-slate-900 dark:text-white">Создание теста → печать → OCR → журнал</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Один маршрут без скачков между разными сервисами и таблицами.
                            </p>
                        </article>
                        <article class="rounded-3xl border border-sky-100 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900/85 dark:shadow-none">
                            <div class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-400 dark:text-slate-500">Чужой бланк</div>
                            <div class="mt-3 text-lg font-bold text-slate-900 dark:text-white">Проверка по тесту даже без локального ученика</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Если тест известен системе, результат можно разобрать без привязки к вашей группе.
                            </p>
                        </article>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <span class="rounded-full border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-indigo-700 dark:bg-white dark:text-slate-950">Варианты тестов</span>
                        <span class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:shadow-none">Предпросмотр печати</span>
                        <span class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:shadow-none">Автовыставление оценки</span>
                    </div>
                </section>
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 py-6 md:py-10">
        <div class="grid gap-5 lg:grid-cols-3">
            <article class="proverium-panel rounded-[2rem] p-6">
                <div class="text-sm font-semibold uppercase tracking-[0.3em] text-indigo-700 dark:text-indigo-300">1. Конструктор</div>
                <h2 class="mt-4 text-2xl font-bold text-slate-950 dark:text-white">Тесты, варианты и шкала оценивания</h2>
                <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-300">
                    Создавайте тест с несколькими вариантами, импортируйте вопросы из JSON и XLSX,
                    задавайте баллы и пороги оценок без дополнительной разметки вручную.
                </p>
            </article>

            <article class="proverium-panel rounded-[2rem] p-6">
                <div class="text-sm font-semibold uppercase tracking-[0.3em] text-indigo-700 dark:text-indigo-300">2. Бланки</div>
                <h2 class="mt-4 text-2xl font-bold text-slate-950 dark:text-white">Персональная печать для группы</h2>
                <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-300">
                    Выпускайте бланки на весь поток или только для выбранных учеников.
                    Макет адаптирован под реальную печать на A4 и повторный скан.
                </p>
            </article>

            <article class="proverium-panel rounded-[2rem] p-6">
                <div class="text-sm font-semibold uppercase tracking-[0.3em] text-indigo-700 dark:text-indigo-300">3. Проверка</div>
                <h2 class="mt-4 text-2xl font-bold text-slate-950 dark:text-white">OCR-разбор и журнал группы</h2>
                <p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-300">
                    Система распознаёт листы, собирает ответы по страницам и даёт результат,
                    который можно сохранить в журнал и выгрузить в XLSX.
                </p>
            </article>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 py-6 md:py-10">
        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <section class="proverium-panel rounded-[2rem] p-6 md:p-8">
                <div class="text-sm font-semibold uppercase tracking-[0.32em] text-slate-400 dark:text-slate-500">Сильные стороны</div>
                <h2 class="mt-4 text-3xl font-black tracking-tight text-slate-950 dark:text-white">
                    Не просто генератор тестов, а полный цикл проверки.
                </h2>
                <div class="mt-6 space-y-4">
                    <div class="rounded-3xl border border-slate-200 bg-white/80 p-4 dark:border-slate-700 dark:bg-slate-900/80">
                        <div class="font-semibold text-slate-900 dark:text-white">Разбор даже при неполной локальной базе</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Чужой бланк можно распознать и проверить по существующему тесту, даже если такого ученика или группы у вас нет.
                        </p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-white/80 p-4 dark:border-slate-700 dark:bg-slate-900/80">
                        <div class="font-semibold text-slate-900 dark:text-white">Структура под преподавателя</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Тесты, группы, печать, OCR и журнал лежат в одном интерфейсе, а не разбросаны по отдельным таблицам и сервисам.
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-[linear-gradient(145deg,_#f8faff,_#eef3ff,_#f1f5ff)] p-6 text-slate-900 shadow-halo dark:border-slate-800 dark:bg-[linear-gradient(145deg,_#0e162b,_#16234a)] dark:text-white md:p-8">
                <div class="grid gap-5 md:grid-cols-3">
                    <article class="rounded-3xl border border-slate-200 bg-white/85 p-5 dark:border-white/10 dark:bg-white/5">
                        <div class="text-4xl font-black text-indigo-600 dark:text-indigo-200">01</div>
                        <div class="mt-3 text-lg font-semibold">Соберите тест</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Добавьте вопросы, варианты, критерии оценивания и шаблон ответа.
                        </p>
                    </article>
                    <article class="rounded-3xl border border-slate-200 bg-white/85 p-5 dark:border-white/10 dark:bg-white/5">
                        <div class="text-4xl font-black text-indigo-600 dark:text-indigo-200">02</div>
                        <div class="mt-3 text-lg font-semibold">Распечатайте бланки</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Выпустите персональные листы для группы или конкретных учеников.
                        </p>
                    </article>
                    <article class="rounded-3xl border border-slate-200 bg-white/85 p-5 dark:border-white/10 dark:bg-white/5">
                        <div class="text-4xl font-black text-indigo-600 dark:text-indigo-200">03</div>
                        <div class="mt-3 text-lg font-semibold">Загрузите сканы</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Провериум разложит страницы, распознает ответы и подготовит результат.
                        </p>
                    </article>
                </div>

                <div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white/80 p-5 dark:border-white/10 dark:bg-white/5">
                    <div class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-400 dark:text-slate-400">Итог</div>
                    <div class="mt-3 text-2xl font-bold">Результат готов к просмотру, сравнению со сканом и записи в журнал группы.</div>
                </div>
            </section>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 pt-6 pb-14 md:pt-10 md:pb-20">
        <div class="rounded-[2.2rem] bg-[linear-gradient(135deg,_#1e293b,_#3a46c7,_#7c8cff)] p-[1px] shadow-halo">
            <div class="rounded-[2.1rem] bg-white px-6 py-8 dark:bg-slate-950 md:px-10 md:py-10">
                <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="max-w-3xl">
                        <div class="text-sm font-semibold uppercase tracking-[0.32em] text-indigo-700 dark:text-indigo-300">Старт</div>
                        <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 dark:text-white md:text-4xl">
                            Запустите кабинет преподавателя и соберите первый тест в Провериуме.
                        </h2>
                        <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                            Регистрация занимает минуту, а дальше можно сразу перейти к созданию теста,
                            выпуску бланков и загрузке сканов.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="/user/register" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                            Создать аккаунт
                        </a>
                        <a href="/user/login" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800">
                            Войти
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

</body>
</html>
