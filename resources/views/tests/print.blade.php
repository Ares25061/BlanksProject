<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="only light">
    <meta name="theme-color" content="#eef1f4">
    <title>{{ $documentTitle ?? ('Печать ' . $test->title) }}</title>
    <script>
        (() => {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            const root = document.documentElement;
            const themeColorMeta = document.querySelector('meta[name="theme-color"]');
            const colorSchemeMeta = document.querySelector('meta[name="color-scheme"]');
            const resolvedTheme = mediaQuery.matches ? 'dark' : 'light';

            root.classList.toggle('dark', resolvedTheme === 'dark');
            root.dataset.theme = resolvedTheme;
            root.dataset.themeMode = 'system';
            root.style.colorScheme = resolvedTheme === 'dark' ? 'dark' : 'only light';
            root.style.backgroundColor = resolvedTheme === 'dark' ? '#0b1115' : '#eef1f4';
            root.style.color = resolvedTheme === 'dark' ? '#e2e8f0' : '#111111';

            if (themeColorMeta) {
                themeColorMeta.setAttribute('content', resolvedTheme === 'dark' ? '#0b1115' : '#eef1f4');
            }

            if (colorSchemeMeta) {
                colorSchemeMeta.setAttribute('content', resolvedTheme === 'dark' ? 'dark' : 'only light');
            }
        })();
    </script>
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

        html[data-theme="light"] {
            background: #eef1f4;
            color: #111111;
            color-scheme: only light;
            forced-color-adjust: none;
        }

        html[data-theme="light"] body {
            background: #eef1f4;
            color: #111111;
            color-scheme: only light;
            forced-color-adjust: none;
        }

        html.dark,
        html.dark body,
        html.dark button {
            color-scheme: dark;
        }

        html.dark {
            background: #0b1115;
            color: #e2e8f0;
            forced-color-adjust: none;
        }

        html.dark body {
            background: #0b1115;
            color: #e2e8f0;
        }

        body {
            margin: 0;
            padding: 18px;
            background: #eef1f4;
            color: #111111;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .screen-note {
            width: 210mm;
            margin: 0 auto 12px;
            padding: 12px 16px;
            border: 1px solid #d0d5dd;
            border-radius: 14px;
            background: #ffffff;
            font-size: 13px;
            line-height: 1.4;
        }

        .toolbar {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 20;
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
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14);
        }

        .toolbar .secondary {
            background: #ffffff;
            color: #111111;
        }

        .toolbar .primary {
            background: #111111;
            color: #ffffff;
        }

        .sheet {
            position: relative;
            width: 210mm;
            height: 297mm;
            margin: 0 auto 16px;
            background: #ffffff;
            color: #111111;
            overflow: hidden;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.12);
            page-break-after: always;
        }

        .marker {
            position: absolute;
            background: #111111;
            z-index: 5;
        }

        .marker::before,
        .marker::after {
            content: "";
            position: absolute;
            background: #ffffff;
        }

        .marker::before {
            left: 16%;
            right: 16%;
            top: 50%;
            height: 0.7mm;
            transform: translateY(-50%);
        }

        .marker::after {
            top: 16%;
            bottom: 16%;
            left: 50%;
            width: 0.7mm;
            transform: translateX(-50%);
        }

        .service-zone {
            position: absolute;
            border: 0.35mm solid #111111;
            border-radius: 4mm;
            padding: 1mm 2.4mm 1mm 2.6mm;
            display: flex;
            align-items: flex-start;
        }

        .service-meta {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.7mm;
            width: 100%;
            height: 100%;
        }

        .service-line {
            margin: 0;
            font-size: 2.9mm;
            line-height: 1.08;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .service-label {
            font-weight: 700;
        }

        .qr-zone {
            position: absolute;
            border: 0.35mm solid #111111;
            border-radius: 3.2mm;
            padding: 0.5mm;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        .qr-zone img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .question-card {
            position: absolute;
            border: 0.35mm solid #111111;
            border-radius: 3mm;
            padding: 3.4mm 3.4mm 3.2mm;
            overflow: hidden;
        }

        .question-title-line {
            margin: 0;
            font-size: 3.45mm;
            line-height: 1.22;
            font-weight: 700;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .question-option-line {
            margin: 0.4mm 0 0;
            font-size: 3.05mm;
            line-height: 1.2;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .question-cells-label {
            position: absolute;
            font-size: 3.2mm;
            line-height: 1;
            white-space: nowrap;
        }

        .answer-cell {
            position: absolute;
            border: 0.32mm solid #111111;
            background: #ffffff;
        }

        .answer-cell-letter {
            position: absolute;
            font-size: 3.6mm;
            line-height: 1;
        }

        .footer-line {
            position: absolute;
            font-size: 3.05mm;
            line-height: 1;
        }

        @media print {
            body {
                padding: 0;
                background: #ffffff;
            }

            .screen-note,
            .toolbar {
                display: none !important;
            }

            .sheet {
                margin: 0;
                box-shadow: none;
            }
        }

        html.dark body {
            background: #020617;
            color: #e2e8f0;
        }

        html.dark .screen-note {
            border-color: #334155;
            background: #0f172a;
            color: #cbd5e1;
        }

        html.dark .toolbar .secondary {
            background: #0f172a;
            color: #e2e8f0;
            box-shadow: 0 14px 28px rgba(2, 6, 23, 0.5);
        }

        html.dark .toolbar .primary {
            background: #f8fafc;
            color: #020617;
            box-shadow: 0 14px 28px rgba(2, 6, 23, 0.5);
        }

        html.dark .sheet {
            box-shadow: 0 22px 56px rgba(2, 6, 23, 0.58);
        }
    </style>
</head>
<body>
@php
    $screenMessage = 'Новый шаблон печатается единым листом: вопросы и клетки находятся на одной странице, а OCR проверяет только клетки.';
@endphp

<div class="toolbar">
    <button class="secondary" onclick="window.history.back()">Назад</button>
    <button class="primary" onclick="window.print()">Печать</button>
</div>

<div class="screen-note">
    {{ $screenMessage }}
</div>

@foreach($blankForms as $blankForm)
    @php
        $pages = $sheetPagesByBlankForm[(int) $blankForm->id] ?? [];
    @endphp

    @foreach($pages as $page)
        @php
            $serviceZone = $page['service_zone'] ?? [];
            $qrZone = $page['qr_zone'] ?? [];
            $markerRects = $page['marker_rects_mm'] ?? [];
        @endphp

        <section class="sheet">
            @foreach($markerRects as $markerRect)
                <div
                    class="marker"
                    style="
                        left: {{ $markerRect['left_mm'] ?? 0 }}mm;
                        top: {{ $markerRect['top_mm'] ?? 0 }}mm;
                        width: {{ $markerRect['width_mm'] ?? 0 }}mm;
                        height: {{ $markerRect['height_mm'] ?? 0 }}mm;
                    "
                ></div>
            @endforeach

            <div
                class="service-zone"
                style="
                    left: {{ $serviceZone['left_mm'] ?? 0 }}mm;
                    top: {{ $serviceZone['top_mm'] ?? 0 }}mm;
                    width: {{ $serviceZone['width_mm'] ?? 0 }}mm;
                    height: {{ $serviceZone['height_mm'] ?? 0 }}mm;
                "
            >
                <div class="service-meta">
                    <p class="service-line"><span class="service-label">Студент:</span> {{ $serviceZone['student_label'] ?? '' }}</p>
                    <p class="service-line"><span class="service-label">Тест:</span> {{ $serviceZone['test_label'] ?? $test->title }}</p>
                    <p class="service-line"><span class="service-label">Группа:</span> {{ $serviceZone['group_label'] ?? 'N/A' }} | <span class="service-label">Стр.:</span> {{ $serviceZone['page_label'] ?? (($page['page_number'] ?? 1) . '/' . ($page['page_count'] ?? 1)) }}</p>
                </div>
            </div>

            <div
                class="qr-zone"
                style="
                    left: {{ $qrZone['left_mm'] ?? 0 }}mm;
                    top: {{ $qrZone['top_mm'] ?? 0 }}mm;
                    width: {{ $qrZone['width_mm'] ?? 0 }}mm;
                    height: {{ $qrZone['height_mm'] ?? 0 }}mm;
                "
            >
                <img src="{{ $page['qr_data_uri'] ?? '' }}" alt="QR">
            </div>

            @foreach(($page['questions'] ?? []) as $question)
                @php
                    $block = $question['block'] ?? ['left_mm' => 0, 'top_mm' => 0, 'width_mm' => 0, 'height_mm' => 0];
                @endphp

                <article
                    class="question-card"
                    style="
                        left: {{ $block['left_mm'] ?? 0 }}mm;
                        top: {{ $block['top_mm'] ?? 0 }}mm;
                        width: {{ $block['width_mm'] ?? 0 }}mm;
                        height: {{ $block['height_mm'] ?? 0 }}mm;
                    "
                >
                    @foreach(($question['title_lines'] ?? []) as $line)
                        <p class="question-title-line">{{ $line }}</p>
                    @endforeach

                    @foreach(($question['option_lines'] ?? []) as $line)
                        @if(trim((string) $line) !== '')
                            <p class="question-option-line">{{ $line }}</p>
                        @endif
                    @endforeach

                    @if(!empty($question['cells']))
                        <div
                            class="question-cells-label"
                            style="
                                left: {{ ($question['cells_label_left_mm'] ?? 0) - ($block['left_mm'] ?? 0) }}mm;
                                top: {{ ($question['cells_label_top_mm'] ?? 0) - ($block['top_mm'] ?? 0) }}mm;
                            "
                        >
                            {{ $question['cells_label'] ?? 'Отметка:' }}
                        </div>
                    @endif

                    @foreach(($question['cells'] ?? []) as $cell)
                        <div
                            class="answer-cell"
                            style="
                                left: {{ ($cell['left_mm'] ?? 0) - ($block['left_mm'] ?? 0) }}mm;
                                top: {{ ($cell['top_mm'] ?? 0) - ($block['top_mm'] ?? 0) }}mm;
                                width: {{ $cell['width_mm'] ?? 0 }}mm;
                                height: {{ $cell['height_mm'] ?? 0 }}mm;
                            "
                        ></div>
                        <div
                            class="answer-cell-letter"
                            style="
                                left: {{ (($cell['left_mm'] ?? 0) - ($block['left_mm'] ?? 0)) + ($cell['width_mm'] ?? 0) + 1.4 }}mm;
                                top: {{ (($cell['top_mm'] ?? 0) - ($block['top_mm'] ?? 0)) + 0.85 }}mm;
                            "
                        >
                            {{ $cell['option_letter'] ?? '' }}
                        </div>
                    @endforeach
                </article>
            @endforeach

            <div
                class="footer-line"
                style="
                    right: {{ $page['footer']['right_mm'] ?? 17 }}mm;
                    bottom: {{ $page['footer']['bottom_mm'] ?? 14 }}mm;
                "
            >
                {{ $page['footer']['text'] ?? '' }}
            </div>
        </section>
    @endforeach
@endforeach
</body>
</html>
