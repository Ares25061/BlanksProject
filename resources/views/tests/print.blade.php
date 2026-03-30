<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $documentTitle ?? ('Печать ' . $test->title) }}</title>
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
            padding: 20px;
            background: #ececec;
            font-family: "Segoe UI", Arial, sans-serif;
            color: #111;
        }

        .screen-note {
            max-width: 210mm;
            margin: 0 auto 14px;
            padding: 14px 18px;
            border: 1px solid #b7b7b7;
            background: #fff;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.45;
        }

        .toolbar {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 10;
            display: flex;
            gap: 10px;
        }

        .toolbar button {
            border: none;
            border-radius: 999px;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.15);
        }

        .toolbar .primary {
            background: #111;
            color: #fff;
        }

        .toolbar .secondary {
            background: #fff;
            color: #111;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 18px;
            background: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
            page-break-after: always;
        }

        .sheet-frame {
            position: absolute;
            inset: 6mm;
            border: 0.25mm solid #b8b8b8;
            border-radius: 3mm;
        }

        .corner-marker {
            position: absolute;
            width: 8mm;
            height: 8mm;
            background: #000;
            z-index: 3;
        }

        .marker-tl { top: 9mm; left: 9mm; }
        .marker-tr { top: 9mm; right: 9mm; }
        .marker-bl { bottom: 9mm; left: 9mm; }
        .marker-br { bottom: 9mm; right: 9mm; }

        .answer-sheet-header {
            position: absolute;
            left: 19mm;
            right: 19mm;
            top: 12mm;
            border: 0.35mm solid #111;
            padding: 2.8mm 4mm 2.4mm;
            min-height: 18mm;
        }

        .answer-sheet-title {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 6mm;
            align-items: start;
            font-size: 4.5mm;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .answer-sheet-title > span:first-child {
            min-width: 0;
            max-width: 118mm;
            line-height: 1.08;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .answer-sheet-title > span:last-child {
            white-space: nowrap;
            text-align: right;
            line-height: 1.08;
        }

        .answer-sheet-subline,
        .answer-sheet-student,
        .answer-sheet-instructions {
            position: absolute;
            left: 14mm;
            right: 14mm;
            font-size: 3mm;
            line-height: 1.45;
        }

        .answer-sheet-subline {
            top: 32mm;
            display: flex;
            justify-content: space-between;
            gap: 4mm;
            padding: 2mm 0;
            border-top: 0.25mm solid #b8b8b8;
            border-bottom: 0.25mm solid #b8b8b8;
            font-weight: 600;
            font-size: 2.85mm;
        }

        .answer-sheet-student {
            top: 42mm;
            display: flex;
            flex-wrap: wrap;
            gap: 4mm 8mm;
            align-items: center;
            padding: 2mm 0;
            border-bottom: 0.25mm solid #d0d0d0;
        }

        .answer-sheet-student span,
        .answer-sheet-subline span {
            white-space: nowrap;
        }

        .answer-sheet-instructions {
            top: 52.5mm;
            padding: 2.2mm 3mm;
            border: 0.25mm solid #b8b8b8;
            background: #fafafa;
            font-size: 2.75mm;
        }

        .scan-column {
            position: absolute;
            border: 0.25mm solid #b8b8b8;
            background: #fff;
        }

        .scan-column-header {
            display: grid;
            align-items: stretch;
            border-bottom: 0.25mm solid #b8b8b8;
            background: #f2f2f2;
            font-size: 2.55mm;
            font-weight: 700;
        }

        .scan-row {
            position: absolute;
            left: 0;
            right: 0;
            display: grid;
            align-items: center;
            border-bottom: 0.18mm solid #d4d4d4;
            font-size: 2.4mm;
            overflow: hidden;
        }

        .scan-row > div,
        .scan-column-header > div {
            padding: 0 1mm;
            display: flex;
            align-items: center;
            min-height: 100%;
        }

        .scan-row > div:not(:last-child),
        .scan-column-header > div:not(:last-child) {
            border-right: 0.18mm solid #d0d0d0;
        }

        .task-no {
            justify-content: center;
            font-weight: 800;
            font-size: 2.8mm;
        }

        .task-text {
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        .task-type {
            justify-content: center;
        }

        .type-pill {
            min-width: 5.4mm;
            padding: 0.35mm 1.2mm;
            border: 0.2mm solid #999;
            border-radius: 999px;
            font-size: 2mm;
            font-weight: 800;
            justify-content: center;
        }

        .answer-row-cell,
        .answer-header-cell {
            padding: 0 !important;
        }

        .header-answer-letters,
        .answer-field {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-answer-letters {
            font-size: 2.1mm;
            color: #444;
        }

        .scan-box {
            width: 3.2mm;
            height: 3.2mm;
            border: 0.22mm solid #555;
            border-radius: 0.45mm;
            background: #fff;
            flex: 0 0 auto;
        }

        .scan-box.disabled {
            border-style: dashed;
            border-color: #c5c5c5;
            background: #f8f8f8;
        }

        .service-matrix {
            position: absolute;
            display: grid;
            gap: 0.6mm;
            z-index: 4;
        }

        .service-dot {
            background: transparent;
            border-radius: 999px;
        }

        .service-dot.black {
            background: #111827;
        }

        .questions-page {
            display: flex;
            flex-direction: column;
            padding: 11mm 12mm 13mm;
        }

        .questions-head,
        .questions-foot {
            display: flex;
            justify-content: space-between;
            gap: 6mm;
            font-size: 3mm;
            line-height: 1.35;
        }

        .questions-head {
            border-bottom: 0.3mm solid #111;
            padding-bottom: 3mm;
            margin-bottom: 4mm;
        }

        .questions-foot {
            border-top: 0.25mm solid #b8b8b8;
            margin-top: auto;
            padding-top: 2.5mm;
            color: #444;
        }

        .questions-title {
            font-size: 4.4mm;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 1.2mm;
            max-width: 110mm;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .questions-content {
            flex: 1 1 auto;
            min-height: 0;
            padding-bottom: 6mm;
        }

        .question-block {
            border: 0.25mm solid #c7c7c7;
            padding: 3.2mm 3.4mm;
            margin-bottom: 3mm;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .question-block:last-child {
            margin-bottom: 0;
        }

        .question-block h3 {
            margin: 0 0 2mm;
            font-size: 3.45mm;
            line-height: 1.45;
        }

        .question-heading {
            display: grid;
            grid-template-columns: 7mm 1fr;
            gap: 2mm;
            align-items: start;
        }

        .question-heading-number {
            font-weight: 800;
            white-space: nowrap;
        }

        .question-heading-text {
            min-width: 0;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .question-meta {
            margin-bottom: 2mm;
            font-size: 2.7mm;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .answer-option {
            display: flex;
            gap: 2.6mm;
            font-size: 3.05mm;
            line-height: 1.45;
            margin-bottom: 1.4mm;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .answer-option strong {
            min-width: 5mm;
        }

        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .screen-note,
            .toolbar {
                display: none !important;
            }

            .sheet {
                margin: 0;
                box-shadow: none;
            }

            .sheet.questions-page {
                height: 297mm;
                min-height: 297mm;
            }

            .questions-head,
            .questions-content,
            .questions-foot,
            .question-block {
                break-inside: avoid-page;
                page-break-inside: avoid;
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
    $columnWidth = BlankScanLayout::questionColumnWidthMm($questionCount);
    $rowHeight = BlankScanLayout::questionRowHeightMm();
    $questionTextWidth = BlankScanLayout::questionTextWidthMm($questionCount);
    $answerFieldOffset = BlankScanLayout::answerFieldLeftOffsetMm($questionCount);
    $answerFieldWidth = BlankScanLayout::answerFieldWidthMm();
    $answerCellWidth = $columnWidth - $answerFieldOffset;
    $hasTooManyAnswers = $test->questions->contains(fn ($question) => $question->answers->count() > count($letters));
    $showAnswerSheets = $printMode !== 'questions';
    $showQuestionSheets = $printMode !== 'blank';
    $answerSheetPageCount = count($answerSheetPages ?? []);
@endphp

<div class="toolbar">
    <button class="secondary" onclick="window.history.back()">Назад</button>
    <button class="primary" onclick="window.print()">Печать</button>
</div>

@if($hasTooManyAnswers)
    <div class="screen-note">
        <strong>Внимание.</strong>
        Автосканирование поддерживает не более {{ count($letters) }} вариантов ответа в одном вопросе.
    </div>
@endif

@if($showAnswerSheets && $answerSheetPageCount > 1)
    <div class="screen-note">
        Для этого теста будет напечатано <strong>{{ $answerSheetPageCount }} сканируемых листа ответов</strong>.
        При проверке загрузите все листы конкретного ученика одной пачкой или одним PDF.
    </div>
@endif

<div class="screen-note">
    Режим печати:
    <strong>
        @if($printMode === 'blank')
            только бланки
        @elseif($printMode === 'questions')
            только задания
        @else
            полный комплект
        @endif
    </strong>
</div>

@foreach($blankForms as $blankForm)
    @php
        $studentName = trim(implode(' ', array_filter([$blankForm->last_name, $blankForm->first_name, $blankForm->patronymic])));
        $criteriaLabel = collect($test->grade_criteria)
            ->sortByDesc('min_points')
            ->map(fn ($criterion) => ($criterion['label'] ?? 'Оценка') . ' от ' . ($criterion['min_points'] ?? 0))
            ->implode(' • ');
    @endphp

    @if($showAnswerSheets)
        @foreach($answerSheetPages as $answerSheetPage)
            @php
                $pageQuestions = $answerSheetPage['questions'];
                $pageBitString = BlankScanLayout::bitStringForPage((int) $blankForm->id, $answerSheetPage['page_number'], $answerSheetPage['page_count']);
                $columnHeight = BlankScanLayout::GRID_BOTTOM_MM - BlankScanLayout::TABLE_TOP_MM;
            @endphp

            <section class="sheet">
                <div class="corner-marker marker-tl"></div>
                <div class="corner-marker marker-tr"></div>
                <div class="corner-marker marker-bl"></div>
                <div class="corner-marker marker-br"></div>
                <div class="sheet-frame"></div>

                <div class="answer-sheet-header">
                    <div class="answer-sheet-title">
                        <span>{{ $test->title }}</span>
                        <span>{{ $test->subject_display_name }}</span>
                    </div>
                </div>

                <div class="answer-sheet-subline">
                    <span>Время: {{ $test->time_limit ? $test->time_limit . ' мин' : 'без лимита' }}</span>
                    <span>Макс. балл: {{ $test->questions->sum('points') }}</span>
                    <span>Заданий: {{ $questionCount }}</span>
                    <span>Лист ответов: {{ $answerSheetPage['page_number'] }} / {{ $answerSheetPage['page_count'] }}</span>
                    <span>Форма: {{ $blankForm->form_number }}</span>
                </div>

                <div class="answer-sheet-student">
                    <span><strong>Студент:</strong> {{ $studentName ?: '____________________' }}</span>
                    <span><strong>Группа:</strong> {{ $blankForm->group_name ?: '____________________' }}</span>
                    <span><strong>Дата:</strong> {{ now()->format('d.m.Y') }}</span>
                    <span><strong>Вопросы:</strong> {{ $answerSheetPage['start_question_number'] }}-{{ $answerSheetPage['end_question_number'] }}</span>
                </div>

                <div class="answer-sheet-instructions">
                    Отмечайте ответы только на этом листе. Для вопроса с одним правильным ответом закрасьте одну клетку, для множественного вопроса можно закрасить несколько клеток.
                </div>

                @php
                    $columnLeft = BlankScanLayout::GRID_LEFT_MM;
                @endphp

                <div class="scan-column" style="top: {{ BlankScanLayout::TABLE_TOP_MM }}mm; left: {{ $columnLeft }}mm; width: {{ $columnWidth }}mm; height: {{ $columnHeight }}mm;">
                    <div class="scan-column-header" style="height: {{ BlankScanLayout::TABLE_HEADER_HEIGHT_MM }}mm; grid-template-columns: {{ BlankScanLayout::QUESTION_NUMBER_WIDTH_MM }}mm {{ $questionTextWidth }}mm {{ BlankScanLayout::QUESTION_TYPE_WIDTH_MM }}mm {{ $answerCellWidth }}mm;">
                        <div style="justify-content:center;">№</div>
                        <div>Вопрос</div>
                        <div style="justify-content:center;">Тип</div>
                        <div class="answer-header-cell">
                            <div class="header-answer-letters" style="width: {{ $answerFieldWidth }}mm;">
                                @foreach($letters as $letter)
                                    <span>{{ $letter }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @foreach($pageQuestions as $rowIndex => $question)
                        @php
                            $globalIndex = $answerSheetPage['start_question_number'] + $rowIndex - 1;
                        @endphp

                        <div class="scan-row" style="top: {{ BlankScanLayout::TABLE_HEADER_HEIGHT_MM + ($rowIndex * $rowHeight) }}mm; height: {{ $rowHeight }}mm; grid-template-columns: {{ BlankScanLayout::QUESTION_NUMBER_WIDTH_MM }}mm {{ $questionTextWidth }}mm {{ BlankScanLayout::QUESTION_TYPE_WIDTH_MM }}mm {{ $answerCellWidth }}mm;">
                            <div class="task-no">{{ $globalIndex + 1 }}</div>
                            <div class="task-text">{{ Str::limit($question->question_text, 90) }}</div>
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

                <div
                    class="service-matrix"
                    aria-hidden="true"
                    style="
                        top: {{ BlankScanLayout::CODE_TOP_MM }}mm;
                        left: {{ BlankScanLayout::codeLeftMm() }}mm;
                        width: {{ BlankScanLayout::codeGridWidthMm() }}mm;
                        height: {{ BlankScanLayout::codeGridHeightMm() }}mm;
                        grid-template-columns: repeat({{ BlankScanLayout::CODE_GRID_COLUMNS }}, {{ BlankScanLayout::CODE_CELL_WIDTH_MM }}mm);
                        grid-template-rows: repeat({{ BlankScanLayout::CODE_GRID_ROWS }}, {{ BlankScanLayout::CODE_CELL_HEIGHT_MM }}mm);
                    "
                >
                    @foreach(str_split($pageBitString) as $bit)
                        <div class="service-dot {{ $bit === '1' ? 'black' : '' }}"></div>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif

    @if($showQuestionSheets)
        @foreach($questionPages as $pageIndex => $pageQuestions)
            <section class="sheet questions-page">
                <div class="questions-head">
                    <div>
                        <div class="questions-title">{{ $test->title }}</div>
                        <div>{{ $test->subject_display_name }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div>{{ $studentName ?: 'Студент не указан' }}</div>
                        <div>{{ $blankForm->group_name ?: 'Группа не указана' }} • Форма {{ $blankForm->form_number }}</div>
                    </div>
                </div>

                <div class="questions-content">
                    @foreach($pageQuestions as $questionData)
                        @php
                            $question = $questionData['question'];
                        @endphp
                        <article class="question-block">
                            <h3 class="question-heading">
                                <span class="question-heading-number">{{ $questionData['number'] }}.</span>
                                <span class="question-heading-text">{{ $question->question_text }}</span>
                            </h3>
                            <div class="question-meta">
                                {{ $question->type === 'single' ? 'Один правильный ответ' : 'Несколько правильных ответов' }} • {{ $question->points }} балл.
                            </div>
                            @foreach($question->answers as $answerIndex => $answer)
                                <div class="answer-option">
                                    <strong>{{ $letters[$answerIndex] ?? ($answerIndex + 1) }}.</strong>
                                    <span>{{ $answer->answer_text }}</span>
                                </div>
                            @endforeach
                        </article>
                    @endforeach
                </div>

                <div class="questions-foot">
                    <div>Страница {{ $pageIndex + 1 }} из {{ count($questionPages) }}</div>
                    <div style="text-align:right;">{{ $criteriaLabel ?: 'Шкала оценивания не указана' }}</div>
                </div>
            </section>
        @endforeach
    @endif
@endforeach
</body>
</html>
