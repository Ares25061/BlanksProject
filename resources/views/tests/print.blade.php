{{-- resources/views/tests/print.blade.php --}}
    <!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бланк ответов | {{ $test->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .blank-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .blank-header {
            background: #1e3a8a;
            color: white;
            padding: 20px 30px;
            border-bottom: 3px solid #fbbf24;
        }

        .blank-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .blank-header .subtitle {
            font-size: 14px;
            margin-top: 5px;
            opacity: 0.9;
        }

        .info-section {
            background: #f8fafc;
            padding: 25px 30px;
            border-bottom: 2px solid #e2e8f0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .cell-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 16px;
            font-family: monospace;
            letter-spacing: 1px;
            text-transform: uppercase;
            background: white;
            transition: all 0.2s;
        }

        .cell-input:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 2px rgba(30, 58, 138, 0.1);
        }

        .test-info {
            background: #eef2ff;
            padding: 15px 30px;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .test-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e3a8a;
        }

        .test-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #334155;
            flex-wrap: wrap;
        }

        .answers-table {
            padding: 30px;
            overflow-x: auto;
        }

        .answers-table table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .answers-table th {
            background: #f1f5f9;
            padding: 12px;
            text-align: center;
            font-weight: 600;
            color: #1e3a8a;
            border: 1px solid #cbd5e1;
            font-size: 14px;
        }

        .answers-table td {
            border: 1px solid #cbd5e1;
            padding: 12px;
            vertical-align: top;
        }

        .question-number-cell {
            background: #f8fafc;
            font-weight: 600;
            color: #1e3a8a;
            width: 60px;
            text-align: center;
            vertical-align: middle;
        }

        .answer-cell {
            width: 150px;
            vertical-align: middle;
        }

        .answer-input {
            width: 100%;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-family: monospace;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .answer-input:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 2px rgba(30, 58, 138, 0.1);
        }

        .question-content {
            font-size: 14px;
            color: #1f2937;
            line-height: 1.5;
        }

        .question-text {
            font-weight: 500;
            margin-bottom: 12px;
        }

        .answers-list {
            margin-top: 8px;
            padding-left: 0;
        }

        .answer-option {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 13px;
            color: #4b5563;
        }

        .answer-letter {
            font-weight: 600;
            color: #1e3a8a;
            min-width: 24px;
        }

        .answer-text {
            flex: 1;
        }

        .points-badge {
            font-size: 11px;
            color: #6b7280;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #e2e8f0;
        }

        .footer-note {
            background: #f1f5f9;
            padding: 20px 30px;
            border-top: 2px solid #cbd5e1;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }

        .print-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #1e3a8a;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(30, 58, 138, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            transition: all 0.3s;
        }

        .print-button:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .code-zone {
            background: #1e293b;
            color: #fbbf24;
            padding: 10px 20px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            text-align: right;
        }

        .barcode-placeholder {
            display: flex;
            align-items: center;
            gap: 20px;
            background: white;
            padding: 10px 15px;
            border-radius: 4px;
        }

        .barcode-placeholder .bars {
            display: flex;
            gap: 3px;
        }

        .barcode-placeholder .bar {
            width: 3px;
            height: 40px;
            background: #000;
        }

        .bar:nth-child(2) { height: 35px; }
        .bar:nth-child(3) { height: 45px; }
        .bar:nth-child(4) { height: 30px; }
        .bar:nth-child(5) { height: 50px; }
        .bar:nth-child(6) { height: 38px; }
        .bar:nth-child(7) { height: 42px; }
        .bar:nth-child(8) { height: 48px; }

        .instruction {
            background: #fef9e3;
            border-left: 4px solid #fbbf24;
            padding: 12px 20px;
            margin: 0 30px 20px 30px;
            font-size: 13px;
            color: #92400e;
        }

        .instruction strong {
            color: #b45309;
        }

        .replacement-section {
            background: #f8fafc;
            padding: 20px 30px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .replacement-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 15px;
        }

        .replacement-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .replacement-item {
            flex: 0 0 calc(33.333% - 15px);
            min-width: 200px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .replacement-item {
                flex: 0 0 calc(50% - 15px);
            }
        }

        @media (max-width: 480px) {
            .replacement-item {
                flex: 0 0 100%;
            }
        }

        .replacement-label {
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            min-width: 70px;
        }

        .replacement-input {
            flex: 1;
            min-width: 100px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 10px;
            font-size: 14px;
            font-family: monospace;
            text-transform: uppercase;
            background: white;
        }

        .replacement-input:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 2px rgba(30, 58, 138, 0.1);
        }

        .replacement-old {
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .blank-container {
                box-shadow: none;
                border-radius: 0;
            }

            .print-button {
                display: none;
            }

            .cell-input, .answer-input, .replacement-input {
                border: 1px solid #000 !important;
                background: white !important;
            }

            .replacement-item {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .instruction {
                display: none !important;
            }

            .replacement-section .text-xs {
                display: none !important;
            }

            .replacement-old {
                display: none !important;
            }

            ::placeholder {
                color: transparent !important;
            }
        }

        .filled-count {
            font-weight: 600;
            color: #1e3a8a;
        }

        .date-field {
            font-weight: 600;
            color: #1e3a8a;
        }

        .letters {
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
<button onclick="prepareAndPrint()" class="print-button">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
    </svg>
    Распечатать бланк
</button>

<div class="blank-container">
    <!-- Шапка -->
    <div class="blank-header">
        <div class="flex justify-between items-center">
            <div>
                <h1>ЕДИНЫЙ ГОСУДАРСТВЕННЫЙ ЭКЗАМЕН</h1>
                <div class="subtitle">БЛАНК ОТВЕТОВ №1</div>
            </div>
            <div class="barcode-placeholder">
                <div class="bars">
                    <div class="bar"></div>
                    <div class="bar"></div>
                    <div class="bar"></div>
                    <div class="bar"></div>
                    <div class="bar"></div>
                    <div class="bar"></div>
                    <div class="bar"></div>
                    <div class="bar"></div>
                </div>
                <span>TEST-{{ str_pad($test->id, 8, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>
    </div>

    <!-- Код региона / Предмет -->
    <div class="code-zone flex justify-between">
        <span>Шифр: {{ strtoupper(substr(md5($test->id), 0, 6)) }}</span>
        <span>Предмет: {{ $test->title }}</span>
        <span>Вариант: {{ rand(1, 4) }}</span>
    </div>

    <!-- Информация об участнике -->
    <div class="info-section">
        <div class="info-grid">
            <div class="info-item">
                <label>ФАМИЛИЯ</label>
                <input type="text" class="cell-input" id="lastName" placeholder="____________________">
            </div>
            <div class="info-item">
                <label>ИМЯ</label>
                <input type="text" class="cell-input" id="firstName" placeholder="____________________">
            </div>
            <div class="info-item">
                <label>ОТЧЕСТВО</label>
                <input type="text" class="cell-input" id="patronymic" placeholder="____________________">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-20">
            <div class="info-item">
                <label>ГРУППА / КЛАСС</label>
                <input type="text" class="cell-input" id="group" placeholder="____________________">
            </div>
            <div class="info-item">
                <label>ДАТА ПРОВЕДЕНИЯ</label>
                <input type="text" class="cell-input date-field" id="currentDate" placeholder="ДД.ММ.ГГГГ">
            </div>
        </div>
    </div>

    <!-- Информация о тесте -->
    <div class="test-info">
        <div class="test-title">{{ $test->title }}</div>
        <div class="test-meta">
            <span>Время: {{ $test->time_limit ?? '90' }} мин</span>
            <span>Всего баллов: {{ $test->questions->sum('points') }}</span>
            <span>Заданий: {{ $test->questions->count() }}</span>
        </div>
    </div>

    <!-- Инструкция (видна только на экране) -->
    <div class="instruction">
        <strong>Инструкция по заполнению:</strong><br>
        • Внимательно заполните все поля заглавными печатными буквами.<br>
        • Для заданий с <strong>ОДНИМ</strong> правильным ответом — напишите букву ответа (А, Б, В, Г).<br>
        • Для заданий с <strong>НЕСКОЛЬКИМИ</strong> правильными ответами — напишите буквы подряд (например: АБВ или АГ).<br>
        • Буквы пишите ЗАГЛАВНЫМИ, четко и разборчиво.<br>
        • В случае ошибки в ответе, укажите правильный ответ в разделе "Замена ошибочных ответов".
    </div>

    <!-- Таблица ответов с вариантами -->
    <div class="answers-table">
        <table>
            <thead>
            <tr>
                <th style="width: 50px">№</th>
                <th style="width: 550px">Вопрос и варианты ответов</th>
                <th style="width: 120px">Ответ</th>
            </tr>
            </thead>
            <tbody>
            @foreach($test->questions as $index => $question)
                @php
                    $letters = ['А', 'Б', 'В', 'Г', 'Д', 'Е'];
                @endphp
                <tr>
                    <td class="question-number-cell">
                        <strong>{{ $index + 1 }}</strong>
                    </td>
                    <td class="question-content">
                        <div class="question-text">{{ $question->question_text }}</div>
                        <div class="answers-list">
                            @foreach($question->answers as $aIndex => $answer)
                                <div class="answer-option">
                                    <span class="answer-letter">{{ $letters[$aIndex] }}.</span>
                                    <span class="answer-text">{{ $answer->answer_text }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="points-badge">Баллов: {{ $question->points }}</div>
                    </td>
                    <td class="answer-cell">
                        <input type="text"
                               class="answer-input"
                               id="answer_{{ $index }}"
                               data-question-type="{{ $question->type }}"
                               data-question-index="{{ $index }}"
                               maxlength="{{ $question->type === 'single' ? 1 : 6 }}"
                               placeholder="______">
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <!-- Замена ошибочных ответов (по количеству вопросов) -->
    <div class="replacement-section">
        <div class="replacement-title">
            Замена ошибочных ответов
        </div>
        <div class="replacement-grid" id="replacementGrid">
            @for($i = 1; $i <= $test->questions->count(); $i++)
                <div class="replacement-item" id="replacementItem_{{ $i - 1 }}">
                    <span class="replacement-label">Задание {{ $i }}:</span>
                    <input type="text"
                           class="replacement-input"
                           id="replacement_{{ $i - 1 }}"
                           data-question-number="{{ $i }}"
                           maxlength="6"
                           placeholder="______">
                    <span class="replacement-old" id="old_answer_{{ $i - 1 }}"></span>
                </div>
            @endfor
        </div>
        <div class="text-xs text-gray-400 mt-3">
            * Если вы ошиблись в ответе, укажите здесь правильный вариант. Ошибочный ответ в основной таблице зачеркните.
        </div>
    </div>

    <!-- Подвал -->
    <div class="footer-note">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>Количество заполненных полей: <span id="filledCount" class="filled-count">0</span></div>
            <div>Подпись организатора: <span class="border-b border-gray-400 inline-block min-w-[150px]" contenteditable="true" id="organizerSignature"></span></div>
            <div>Штрих-код: {{ strtoupper(uniqid()) }}</div>
        </div>
    </div>
</div>

<script>
    // Устанавливаем текущую дату
    const today = new Date();
    const day = String(today.getDate()).padStart(2, '0');
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const year = today.getFullYear();
    const currentDateInput = document.getElementById('currentDate');
    if (currentDateInput) {
        currentDateInput.value = `${day}.${month}.${year}`;
    }

    // Функция для показа старого ответа в поле замены (только на экране)
    function updateReplacementOldAnswers() {
        const answers = document.querySelectorAll('.answer-input');
        answers.forEach((answer, index) => {
            const oldAnswerSpan = document.getElementById(`old_answer_${index}`);
            if (oldAnswerSpan && answer.value) {
                oldAnswerSpan.textContent = `(было: ${answer.value})`;
            } else if (oldAnswerSpan) {
                oldAnswerSpan.textContent = '';
            }
        });
    }

    // Функция подсчета заполненных полей
    function updateFilledCount() {
        let count = 0;

        // Считаем заполненные поля информации
        const infoFields = ['lastName', 'firstName', 'patronymic', 'group', 'currentDate'];
        infoFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && field.value && field.value.trim() !== '') {
                count++;
            }
        });

        // Подпись организатора
        const organizerSig = document.getElementById('organizerSignature');
        if (organizerSig && organizerSig.textContent && organizerSig.textContent.trim() !== '') {
            count++;
        }

        // Считаем заполненные ответы
        const answers = document.querySelectorAll('.answer-input');
        answers.forEach(answer => {
            if (answer.value && answer.value.trim() !== '') {
                count++;
            }
        });

        // Считаем заполненные поля замены
        const replacementFields = document.querySelectorAll('.replacement-input');
        replacementFields.forEach(field => {
            if (field.value && field.value.trim() !== '') {
                count++;
            }
        });

        const countSpan = document.getElementById('filledCount');
        if (countSpan) {
            countSpan.textContent = count;
        }
    }

    // Обработка ввода ответов
    function setupAnswerInputs() {
        const inputs = document.querySelectorAll('.answer-input');

        inputs.forEach(input => {
            const questionType = input.dataset.questionType;

            input.addEventListener('input', function(e) {
                let value = this.value.toUpperCase();

                // Оставляем только буквы А-Я
                value = value.replace(/[^А-Я]/g, '');

                // Для одиночного выбора - только 1 символ
                if (questionType === 'single' && value.length > 1) {
                    value = value.charAt(0);
                }

                // Для множественного выбора - не более 6 символов
                if (questionType === 'multiple' && value.length > 6) {
                    value = value.slice(0, 6);
                }

                this.value = value;
                updateFilledCount();
                updateReplacementOldAnswers();
            });
        });
    }

    // Настройка полей информации
    function setupInfoFields() {
        const infoInputs = document.querySelectorAll('#lastName, #firstName, #patronymic, #group, #currentDate');
        infoInputs.forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
                updateFilledCount();
            });
        });

        // Подпись организатора (contenteditable)
        const organizerSig = document.getElementById('organizerSignature');
        if (organizerSig) {
            organizerSig.addEventListener('input', updateFilledCount);
            organizerSig.addEventListener('blur', function() {
                if (this.textContent.trim() === '') {
                    this.innerHTML = '';
                }
                updateFilledCount();
            });
        }

        // Поля замены
        const replacementFields = document.querySelectorAll('.replacement-input');
        replacementFields.forEach(field => {
            field.addEventListener('input', function() {
                let value = this.value.toUpperCase();
                value = value.replace(/[^А-Я]/g, '');
                if (value.length > 6) {
                    value = value.slice(0, 6);
                }
                this.value = value;
                updateFilledCount();
            });
        });
    }

    // Функция для подготовки к печати
    function prepareAndPrint() {
        // Делаем все поля нередактируемыми
        document.querySelectorAll('input').forEach(input => {
            input.setAttribute('readonly', true);
            input.style.backgroundColor = '#f9fafb';
        });

        document.querySelectorAll('[contenteditable="true"]').forEach(field => {
            field.setAttribute('contenteditable', 'false');
            field.style.backgroundColor = '#f9fafb';
        });

        // Убираем подсказки перед печатью
        document.querySelectorAll('.answer-input, .cell-input, .replacement-input').forEach(input => {
            input.setAttribute('placeholder', '');
        });

        // Печатаем
        window.print();

        // Возвращаем редактирование после печати
        setTimeout(() => {
            document.querySelectorAll('input').forEach(input => {
                input.removeAttribute('readonly');
                input.style.backgroundColor = '';
                // Восстанавливаем placeholder
                if (input.classList.contains('answer-input')) {
                    input.setAttribute('placeholder', '______');
                } else if (input.classList.contains('cell-input') && input.id !== 'currentDate') {
                    input.setAttribute('placeholder', '____________________');
                } else if (input.id === 'currentDate') {
                    input.setAttribute('placeholder', 'ДД.ММ.ГГГГ');
                } else if (input.classList.contains('replacement-input')) {
                    input.setAttribute('placeholder', '______');
                }
            });

            document.querySelectorAll('[contenteditable="true"]').forEach(field => {
                field.setAttribute('contenteditable', 'true');
                field.style.backgroundColor = '';
            });
        }, 100);
    }

    // Функция для сбора всех данных
    function collectFormData() {
        const formData = {
            test_id: {{ $test->id }},
            last_name: document.getElementById('lastName')?.value || '',
            first_name: document.getElementById('firstName')?.value || '',
            patronymic: document.getElementById('patronymic')?.value || '',
            group_name: document.getElementById('group')?.value || '',
            submission_date: document.getElementById('currentDate')?.value || '',
            answers: {},
            replacements: {}
        };

        const answers = document.querySelectorAll('.answer-input');
        answers.forEach((answer, index) => {
            if (answer.value) {
                formData.answers[index] = answer.value;
            }
        });

        const replacements = document.querySelectorAll('.replacement-input');
        replacements.forEach((replacement, index) => {
            if (replacement.value) {
                formData.replacements[index] = replacement.value;
            }
        });

        return formData;
    }

    // Инициализация
    document.addEventListener('DOMContentLoaded', function() {
        setupAnswerInputs();
        setupInfoFields();
        updateFilledCount();

        // Добавляем CSS для печати
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                input, [contenteditable="true"] {
                    border: 1px solid #000 !important;
                    background: white !important;
                }
                .replacement-item {
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                .instruction {
                    display: none !important;
                }
                .replacement-section .text-xs {
                    display: none !important;
                }
                .replacement-old {
                    display: none !important;
                }
                ::placeholder {
                    color: transparent !important;
                }
            }
        `;
        document.head.appendChild(style);

        // Автосохранение
        function autoSave() {
            const data = collectFormData();
            localStorage.setItem(`test_blank_${data.test_id}`, JSON.stringify(data));
        }

        setInterval(autoSave, 5000);

        // Восстановление сохраненных данных
        const savedData = localStorage.getItem(`test_blank_{{ $test->id }}`);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                if (data.last_name) document.getElementById('lastName').value = data.last_name;
                if (data.first_name) document.getElementById('firstName').value = data.first_name;
                if (data.patronymic) document.getElementById('patronymic').value = data.patronymic;
                if (data.group_name) document.getElementById('group').value = data.group_name;
                if (data.submission_date) document.getElementById('currentDate').value = data.submission_date;

                if (data.answers) {
                    Object.keys(data.answers).forEach(index => {
                        const answerInput = document.getElementById(`answer_${index}`);
                        if (answerInput) answerInput.value = data.answers[index];
                    });
                }

                if (data.replacements) {
                    Object.keys(data.replacements).forEach(index => {
                        const replacementInput = document.getElementById(`replacement_${index}`);
                        if (replacementInput) replacementInput.value = data.replacements[index];
                    });
                }

                updateFilledCount();
                updateReplacementOldAnswers();
            } catch(e) {
                console.error('Error restoring saved data:', e);
            }
        }
    });
</script>
</body>
</html>
