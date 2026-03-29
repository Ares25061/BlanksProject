<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Печать бланков | {{ $test->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #f3f4f6;
            font-family: "Segoe UI", sans-serif;
            color: #111111;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 18px;
            background: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        }

        .answer-sheet {
            page-break-after: always;
        }

        .booklet-page {
            padding: 16mm 14mm;
            page-break-after: always;
        }

        .corner-marker {
            position: absolute;
            width: 8mm;
            height: 8mm;
            background: #000;
            z-index: 10;
        }

        .marker-tl { top: 9mm; left: 9mm; }
        .marker-tr { top: 9mm; right: 9mm; }
        .marker-bl { bottom: 9mm; left: 9mm; }
        .marker-br { bottom: 9mm; right: 9mm; }

        .sheet-frame {
            position: absolute;
            inset: 6mm 6mm 6mm 6mm;
            border-radius: 3mm;
            overflow: hidden;
            border: 0.25mm solid #b8b8b8;
            background: #ffffff;
        }

        .hero-banner {
            position: absolute;
            left: 10mm;
            right: 10mm;
            top: 10mm;
            height: 14mm;
            border-radius: 2.6mm 2.6mm 0 0;
            background: #ffffff;
            border: 0.35mm solid #111111;
            border-bottom: none;
        }

        .hero-title {
            font-size: 7.1mm;
            line-height: 0.95;
            font-weight: 800;
            letter-spacing: 0.01em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .hero-subtitle {
            margin-top: 1.2mm;
            font-size: 3.4mm;
            font-weight: 600;
            opacity: 0.95;
            text-transform: uppercase;
        }

        .service-matrix {
            position: absolute;
            top: 273.8mm;
            left: 79.7mm;
            width: 50.6mm;
            height: 5.8mm;
            display: grid;
            grid-template-columns: repeat(16, 2.6mm);
            grid-template-rows: repeat(2, 2.6mm);
            gap: 0.6mm;
            z-index: 12;
        }

        .service-dot {
            background: transparent;
            border-radius: 999px;
        }

        .service-dot.black {
            background: #111827;
        }

        .hero-meta-strip {
            position: absolute;
            left: 10mm;
            right: 10mm;
            top: 24mm;
            height: 8mm;
            background: #ffffff;
            color: #111111;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 4mm;
            border: 0.35mm solid #111111;
            font-size: 3.2mm;
        }

        .hero-meta-strip span {
            white-space: nowrap;
        }

        .hero-meta-center {
            flex: 1;
            text-align: center;
        }

        .identity-row {
            position: absolute;
            left: 14mm;
            right: 14mm;
            display: grid;
            gap: 4mm;
        }

        .identity-row-primary {
            top: 45mm;
            grid-template-columns: repeat(3, 1fr);
        }

        .identity-row-secondary {
            top: 62mm;
            grid-template-columns: repeat(2, 1fr);
            right: 76mm;
        }

        .info-field {
            min-height: 10.5mm;
        }

        .info-label {
            font-size: 2.6mm;
            font-weight: 700;
            color: #555555;
            text-transform: uppercase;
            margin-bottom: 3.8mm;
        }

        .info-line {
            border-bottom: 0.25mm dashed #777777;
            padding-bottom: 1.3mm;
            min-height: 6mm;
            font-size: 4.2mm;
            font-weight: 700;
            color: #111111;
        }

        .test-strip {
            position: absolute;
            left: 10mm;
            right: 10mm;
            top: 82mm;
            height: 12mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 4.5mm;
            background: #f2f2f2;
            border-top: 0.25mm solid #9a9a9a;
            border-bottom: 0.25mm solid #9a9a9a;
        }

        .test-strip-title {
            font-size: 5mm;
            font-weight: 700;
            color: #111111;
        }

        .test-strip-meta {
            display: flex;
            gap: 6mm;
            font-size: 3.2mm;
            color: #333333;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .instructions {
            position: absolute;
            left: 14mm;
            right: 14mm;
            top: 96mm;
            background: #ffffff;
            border: 0.25mm solid #9a9a9a;
            border-left: 0.8mm solid #111111;
            padding: 3mm 4mm;
            color: #111111;
            font-size: 2.85mm;
            line-height: 1.45;
        }

        .scan-column {
            position: absolute;
            top: 108mm;
            height: 143.5mm;
            border: 0.25mm solid #b8b8b8;
            background: #ffffff;
        }

        .scan-column-header {
            height: 7.5mm;
            display: grid;
            align-items: stretch;
            border-bottom: 0.25mm solid #b8b8b8;
            background: #f2f2f2;
            font-size: 2.6mm;
            font-weight: 700;
            color: #111111;
        }

        .scan-row {
            position: absolute;
            left: 0;
            right: 0;
            display: grid;
            align-items: center;
            border-bottom: 0.18mm solid #d4d4d4;
            font-size: 2.45mm;
            color: #111111;
            overflow: hidden;
        }

        .scan-row > div,
        .scan-column-header > div {
            padding: 0 1.1mm;
            display: flex;
            align-items: center;
            min-height: 100%;
            overflow: hidden;
        }

        .scan-row > div:not(:last-child),
        .scan-column-header > div:not(:last-child) {
            border-right: 0.18mm solid #d0d0d0;
        }

        .task-no {
            justify-content: center;
            font-size: 2.9mm;
            font-weight: 800;
            color: #111111;
        }

        .task-text {
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .task-type {
            justify-content: center;
        }

        .answer-header-cell,
        .answer-row-cell {
            padding: 0 !important;
            justify-content: flex-start !important;
        }

        .type-pill {
            min-width: 5.8mm;
            padding: 0.5mm 1.4mm;
            border-radius: 999px;
            background: #f1f1f1;
            color: #111111;
            border: 0.2mm solid #9a9a9a;
            font-size: 2.15mm;
            font-weight: 800;
            justify-content: center;
            display: inline-flex;
            align-items: center;
        }

        .answer-field {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 1.1mm;
            margin-left: 0;
        }

        .scan-box {
            width: 3.2mm;
            height: 3.2mm;
            border: 0.22mm solid #555555;
            border-radius: 0.45mm;
            background: #fff;
            flex: 0 0 auto;
        }

        .scan-box.disabled {
            border-style: dashed;
            background: #f9f9f9;
            border-color: #c5c5c5;
        }

        .header-answer-letters {
            width: 100%;
            display: flex;
            justify-content: space-between;
            font-size: 2.2mm;
            color: #444444;
            letter-spacing: 0.03em;
        }

        .screen-note {
            max-width: 210mm;
            margin: 0 auto 18px;
            background: #ffffff;
            color: #111111;
            border: 1px solid #9a9a9a;
            border-radius: 20px;
            padding: 16px 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .toolbar {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 1000;
            display: flex;
            gap: 12px;
        }

        .toolbar button {
            border: none;
            border-radius: 999px;
            padding: 14px 22px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18);
        }

        .toolbar .primary {
            background: #111111;
            color: #fff;
        }

        .toolbar .secondary {
            background: #fff;
            color: #111111;
        }

        .booklet-header {
            display: flex;
            justify-content: space-between;
            gap: 10mm;
            align-items: start;
            border-bottom: 0.35mm solid #b8b8b8;
            padding-bottom: 5mm;
            margin-bottom: 7mm;
        }

        .booklet-title {
            font-size: 9mm;
            font-weight: 800;
            color: #111111;
        }

        .booklet-subtitle {
            margin-top: 1.5mm;
            color: #444444;
            font-size: 3.6mm;
        }

        .booklet-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 7mm;
        }

        .question-card {
            border: 0.3mm solid #b8b8b8;
            border-radius: 4mm;
            padding: 4.5mm;
            break-inside: avoid;
            background: #ffffff;
        }

        .question-card h3 {
            margin: 0 0 3.5mm;
            font-size: 4mm;
            line-height: 1.45;
            color: #111111;
        }

        .answer-option {
            display: flex;
            gap: 2.6mm;
            font-size: 3.1mm;
            line-height: 1.45;
            margin-bottom: 2mm;
        }

        .answer-option strong {
            color: #111111;
        }

        .booklet-footer {
            margin-top: 8mm;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6mm;
        }

        .info-panel {
            border: 0.3mm solid #b8b8b8;
            border-radius: 4mm;
            padding: 4mm;
            background: #f7f7f7;
            font-size: 3mm;
            line-height: 1.6;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .sheet {
                margin: 0;
                box-shadow: none;
            }

            .screen-note,
            .toolbar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
@php
    use App\Support\BlankScanLayout;
    use Illuminate\Support\Str;

    $letters = BlankScanLayout::answerLetters();
    $questionCount = $test->questions->count();
    $columnCount = BlankScanLayout::questionColumnCount($questionCount);
    $columnWidth = BlankScanLayout::questionColumnWidthMm($questionCount);
    $rowHeight = BlankScanLayout::questionRowHeightMm();
    $questionTextWidth = BlankScanLayout::questionTextWidthMm($questionCount);
    $answerFieldOffset = BlankScanLayout::answerFieldLeftOffsetMm($questionCount);
    $answerFieldWidth = BlankScanLayout::answerFieldWidthMm();
    $answerCellWidth = $columnWidth - $answerFieldOffset;
    $hasTooManyQuestions = $questionCount > $maxScannableQuestions;
    $hasTooManyAnswers = $test->questions->contains(fn ($question) => $question->answers->count() > count($letters));
@endphp

<div class="toolbar">
    <button class="secondary" onclick="window.history.back()">Назад</button>
    <button class="primary" onclick="window.print()">Печать</button>
</div>

@if($hasTooManyQuestions || $hasTooManyAnswers)
    <div class="screen-note">
        <strong>Внимание.</strong>
        Автоматическое сканирование сейчас работает для тестов до {{ $maxScannableQuestions }} вопросов
        и не более чем с {{ count($letters) }} вариантами ответа в одном вопросе.
    </div>
@endif

@foreach($blankForms as $blankForm)
    @php
        $bitString = BlankScanLayout::bitStringFor((int) $blankForm->id);
        $studentName = trim(implode(' ', array_filter([$blankForm->last_name, $blankForm->first_name, $blankForm->patronymic])));
        $isPreview = (bool) data_get($blankForm, 'metadata.is_preview', false);
    @endphp

    <section class="sheet answer-sheet">
        <div class="corner-marker marker-tl"></div>
        <div class="corner-marker marker-tr"></div>
        <div class="corner-marker marker-bl"></div>
        <div class="corner-marker marker-br"></div>

        <div class="sheet-frame"></div>

        <div class="hero-banner"></div>

        <div class="hero-meta-strip">
            <span>&nbsp;</span>
            <span class="hero-meta-center"><strong>Предмет:</strong> {{ $test->subject_display_name }}</span>
            <span>&nbsp;</span>
        </div>

        <div class="identity-row identity-row-primary">
            <div class="info-field">
                <div class="info-label">Фамилия</div>
                <div class="info-line">{{ $blankForm->last_name ?: '____________________' }}</div>
            </div>

            <div class="info-field">
                <div class="info-label">Имя</div>
                <div class="info-line">{{ $blankForm->first_name ?: '____________________' }}</div>
            </div>

            <div class="info-field">
                <div class="info-label">Отчество (при наличии)</div>
                <div class="info-line">{{ $blankForm->patronymic ?: '____________________' }}</div>
            </div>
        </div>

        <div class="identity-row identity-row-secondary">
            <div class="info-field">
                <div class="info-label">Группа / класс</div>
                <div class="info-line">{{ $blankForm->group_name ?: '____________________' }}</div>
            </div>

            <div class="info-field">
                <div class="info-label">Дата проведения</div>
                <div class="info-line">{{ now()->format('d.m.Y') }}</div>
            </div>
        </div>

        <div class="test-strip">
            <div class="test-strip-title">{{ $test->title }}</div>
            <div class="test-strip-meta">
                <span>Время: {{ $test->time_limit ?? 'Без лимита' }}{{ $test->time_limit ? ' мин' : '' }}</span>
                <span>Всего баллов: {{ $test->questions->sum('points') }}</span>
                <span>Заданий: {{ $questionCount }}</span>
            </div>
        </div>

        <div class="instructions">
            <strong>Инструкция по заполнению:</strong>
            для заданий с одним правильным ответом закрасьте одну клетку,
            для заданий с несколькими правильными ответами можно закрасить несколько клеток.
            Отмечайте ответы только на этом первом листе.
            @if($isPreview)
                <strong>Этот экземпляр демонстрационный:</strong> он нужен для проверки верстки, а не для реальной загрузки скана.
            @endif
        </div>

        @for($columnIndex = 0; $columnIndex < $columnCount; $columnIndex++)
            @php
                $columnQuestions = $test->questions->values()->slice($columnIndex * BlankScanLayout::GRID_ROWS_PER_COLUMN, BlankScanLayout::GRID_ROWS_PER_COLUMN)->values();
                $columnLeft = BlankScanLayout::GRID_LEFT_MM + ($columnIndex * ($columnWidth + BlankScanLayout::GRID_COLUMN_GAP_MM));
            @endphp

            <div class="scan-column" style="left: {{ $columnLeft }}mm; width: {{ $columnWidth }}mm;">
                <div class="scan-column-header" style="grid-template-columns: {{ BlankScanLayout::QUESTION_NUMBER_WIDTH_MM }}mm {{ $questionTextWidth }}mm {{ BlankScanLayout::QUESTION_TYPE_WIDTH_MM }}mm {{ $answerCellWidth }}mm;">
                    <div style="justify-content:center;">№</div>
                    <div>Текст вопроса</div>
                    <div style="justify-content:center;">Тип</div>
                    <div class="answer-header-cell">
                        <div class="header-answer-letters" style="width: {{ $answerFieldWidth }}mm;">
                            @foreach($letters as $letter)
                                <span>{{ $letter }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                @foreach($columnQuestions as $rowIndex => $question)
                    @php
                        $globalIndex = ($columnIndex * BlankScanLayout::GRID_ROWS_PER_COLUMN) + $rowIndex;
                    @endphp

                    <div class="scan-row" style="top: {{ BlankScanLayout::TABLE_HEADER_HEIGHT_MM + ($rowIndex * $rowHeight) }}mm; height: {{ $rowHeight }}mm; grid-template-columns: {{ BlankScanLayout::QUESTION_NUMBER_WIDTH_MM }}mm {{ $questionTextWidth }}mm {{ BlankScanLayout::QUESTION_TYPE_WIDTH_MM }}mm {{ $answerCellWidth }}mm;">
                        <div class="task-no">{{ $globalIndex + 1 }}</div>
                        <div class="task-text">{{ Str::limit($question->question_text, 80) }}</div>
                        <div class="task-type">
                            <span class="type-pill">{{ $question->type === 'single' ? '1' : 'М' }}</span>
                        </div>
                        <div class="answer-row-cell">
                            <div class="answer-field" style="width: {{ $answerFieldWidth }}mm;">
                                @for($optionIndex = 0; $optionIndex < count($letters); $optionIndex++)
                                    <span class="scan-box {{ $optionIndex >= $question->answers->count() ? 'disabled' : '' }}"></span>
                                @endfor
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endfor

        <div class="service-matrix" aria-hidden="true">
            @foreach(str_split($bitString) as $bit)
                <div class="service-dot {{ $bit === '1' ? 'black' : '' }}"></div>
            @endforeach
        </div>
    </section>

    <section class="sheet booklet-page">
        <div class="booklet-header">
            <div>
                <div class="booklet-title">{{ $test->title }}</div>
                <div class="booklet-subtitle">{{ $test->description ?: 'Описание не указано' }}</div>
            </div>

            <div class="info-panel" style="min-width: 62mm;">
                <strong>Студент:</strong> {{ $studentName ?: 'Не указан' }}<br>
                <strong>Группа:</strong> {{ $blankForm->group_name ?: 'Не указана' }}<br>
                <strong>Предмет:</strong> {{ $test->subject_display_name }}<br>
                <strong>Форма:</strong> {{ $blankForm->form_number }}<br>
                <strong>Время:</strong> {{ $test->time_limit ? $test->time_limit . ' мин' : 'Без лимита' }}
            </div>
        </div>

        <div class="booklet-grid">
            @foreach($test->questions->values() as $index => $question)
                <article class="question-card">
                    <h3>{{ $index + 1 }}. {{ $question->question_text }}</h3>

                    @foreach($question->answers as $answerIndex => $answer)
                        <div class="answer-option">
                            <strong>{{ $letters[$answerIndex] ?? ($answerIndex + 1) }}.</strong>
                            <span>{{ $answer->answer_text }}</span>
                        </div>
                    @endforeach

                    <div class="text-[2.7mm] uppercase tracking-[0.2em] text-slate-400 mt-4">
                        {{ $question->type === 'single' ? 'Один правильный ответ' : 'Несколько правильных ответов' }} • {{ $question->points }} балл.
                    </div>
                </article>
            @endforeach
        </div>

        <div class="booklet-footer">
            <div class="info-panel">
                <strong>Шкала оценивания</strong><br>
                @foreach(collect($test->grade_criteria)->sortByDesc('min_points') as $criterion)
                    {{ $criterion['label'] }}: от {{ $criterion['min_points'] }} балл.<br>
                @endforeach
            </div>

            <div class="info-panel">
                <strong>Важно для автосканирования</strong><br>
                После выполнения теста ученик должен сдать именно первый лист с таблицей ответов.
                Пометки нужно делать в клетках, без обводок вне поля и без сгибов по краям листа.
            </div>
        </div>
    </section>
@endforeach
</body>
</html>
