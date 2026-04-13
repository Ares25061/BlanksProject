<?php

namespace App\Services;

use App\Models\BlankForm;
use App\Models\Test;
use App\Support\UnifiedSheetLayout;
use Illuminate\Support\Collection;

class UnifiedSheetLayoutService
{
    public function __construct(
        private TestVariantService $testVariantService,
        private BlankSheetQrCodeService $blankSheetQrCodeService,
    ) {
    }

    public function buildPagesForBlankForm(BlankForm $blankForm): array
    {
        $blankForm->loadMissing('test.questions.answers');

        return $this->buildPages(
            $blankForm->test,
            $blankForm->variant_number ?? 1,
            [
                'blank_form_id' => (int) $blankForm->id,
                'form_number' => (string) $blankForm->form_number,
                'student_name' => $blankForm->student_full_name,
                'group_name' => (string) ($blankForm->group_name ?? ''),
                'variant_number' => (int) ($blankForm->variant_number ?? 1),
            ]
        );
    }

    public function buildPagesForPreview(Test $test, array $payload = []): array
    {
        return $this->buildPages(
            $test->loadMissing('questions.answers'),
            (int) ($payload['variant_number'] ?? 1),
            [
                'blank_form_id' => (int) ($payload['blank_form_id'] ?? 0),
                'form_number' => (string) ($payload['form_number'] ?? 'PREVIEW'),
                'student_name' => trim((string) ($payload['student_name'] ?? '')),
                'group_name' => trim((string) ($payload['group_name'] ?? '')),
                'variant_number' => (int) ($payload['variant_number'] ?? 1),
            ]
        );
    }

    protected function buildPages(Test $test, int $variantNumber, array $identity): array
    {
        $normalizedVariantNumber = $this->testVariantService->normalizeVariantNumber($test, $variantNumber);
        $questions = $this->testVariantService->questionsForVariant($test, $normalizedVariantNumber)->values();

        $pages = [];
        $currentPageQuestions = [];
        $currentColumn = 0;
        $currentTopMm = UnifiedSheetLayout::questionAreaTopMm();

        foreach ($questions as $index => $question) {
            $questionNumber = $index + 1;
            $variantAnswers = $this->testVariantService->orderedAnswersForQuestion($question, $normalizedVariantNumber)->values();
            $questionManifest = $this->buildQuestionManifest(
                $question,
                $variantAnswers,
                $questionNumber,
                $currentColumn,
                $currentTopMm
            );

            if (
                !empty($currentPageQuestions)
                && ($questionManifest['block']['top_mm'] + $questionManifest['block']['height_mm']) > UnifiedSheetLayout::questionAreaBottomMm()
            ) {
                if ($currentColumn + 1 < UnifiedSheetLayout::COLUMN_COUNT) {
                    $currentColumn++;
                    $currentTopMm = UnifiedSheetLayout::questionAreaTopMm();
                } else {
                    $pages[] = $currentPageQuestions;
                    $currentPageQuestions = [];
                    $currentColumn = 0;
                    $currentTopMm = UnifiedSheetLayout::questionAreaTopMm();
                }

                $questionManifest = $this->buildQuestionManifest(
                    $question,
                    $variantAnswers,
                    $questionNumber,
                    $currentColumn,
                    $currentTopMm
                );
            }

            $currentPageQuestions[] = $questionManifest;
            $currentTopMm += $questionManifest['block']['height_mm'] + UnifiedSheetLayout::QUESTION_GAP_MM;
        }

        if (empty($currentPageQuestions)) {
            $currentPageQuestions[] = $this->buildEmptyQuestionPlaceholder();
        }

        if (!empty($currentPageQuestions)) {
            $pages[] = $currentPageQuestions;
        }

        $pageCount = count($pages);
        $maxScore = (int) $questions->sum('points');

        return collect($pages)
            ->values()
            ->map(function (array $pageQuestions, int $pageIndex) use ($identity, $normalizedVariantNumber, $pageCount, $maxScore, $test) {
                $pageNumber = $pageIndex + 1;
                $firstQuestion = collect($pageQuestions)->first(fn (array $question) => !($question['is_placeholder'] ?? false));
                $lastQuestion = collect($pageQuestions)->reverse()->first(fn (array $question) => !($question['is_placeholder'] ?? false));
                $qrPayload = $this->blankSheetQrCodeService->buildPayload([
                    'blank_form_id' => (int) ($identity['blank_form_id'] ?? 0),
                    'form_number' => (string) ($identity['form_number'] ?? ''),
                    'page_number' => $pageNumber,
                    'page_count' => $pageCount,
                ]);

                return [
                    'manifest_version' => UnifiedSheetLayout::VERSION,
                    'blank_form_id' => (int) ($identity['blank_form_id'] ?? 0),
                    'form_number' => (string) ($identity['form_number'] ?? ''),
                    'student_name' => (string) ($identity['student_name'] ?? ''),
                    'group_name' => (string) ($identity['group_name'] ?? ''),
                    'variant_number' => $normalizedVariantNumber,
                    'page_number' => $pageNumber,
                    'page_count' => $pageCount,
                    'page_width_mm' => UnifiedSheetLayout::PAGE_WIDTH_MM,
                    'page_height_mm' => UnifiedSheetLayout::PAGE_HEIGHT_MM,
                    'marker_centers_mm' => UnifiedSheetLayout::markerCentersMm(),
                    'marker_rects_mm' => UnifiedSheetLayout::markerRectsMm(),
                    'service_zone' => array_merge(UnifiedSheetLayout::serviceZoneMm(), [
                        'title' => (string) $test->title,
                        'student_label' => (string) (($identity['student_name'] ?? '') !== '' ? $identity['student_name'] : 'УНИВЕРСАЛЬНЫЙ БЛАНК'),
                        'student_id_label' => (string) ((int) ($identity['blank_form_id'] ?? 0) > 0 ? $identity['blank_form_id'] : 'N/A'),
                        'group_label' => (string) (($identity['group_name'] ?? '') !== '' ? $identity['group_name'] : 'N/A'),
                        'test_label' => (string) $test->title,
                        'version_label' => 'V' . $normalizedVariantNumber,
                        'form_label' => (string) ($identity['form_number'] ?? ''),
                        'page_label' => $pageNumber . '/' . $pageCount,
                        'max_score' => $maxScore,
                    ]),
                    'qr_zone' => UnifiedSheetLayout::qrZoneMm(),
                    'qr_payload' => $qrPayload,
                    'question_range' => [
                        'start' => $firstQuestion['question_number'] ?? 0,
                        'end' => $lastQuestion['question_number'] ?? 0,
                    ],
                    'footer' => [
                        'text' => trim((string) (($identity['form_number'] ?? '') . ' | ' . $pageNumber . '/' . $pageCount)),
                    ],
                    'questions' => array_values($pageQuestions),
                ];
            })
            ->all();
    }

    protected function buildQuestionManifest($question, Collection $variantAnswers, int $questionNumber, int $columnIndex, float $topMm): array
    {
        $blockLeftMm = UnifiedSheetLayout::columnLeftMm($columnIndex);
        $blockWidthMm = UnifiedSheetLayout::columnWidthMm();
        $innerWidthMm = $blockWidthMm - (UnifiedSheetLayout::QUESTION_INNER_PADDING_MM * 2);

        $titleLines = $this->wrapText(
            '[' . $questionNumber . '] ' . trim((string) $question->question_text),
            $innerWidthMm,
            UnifiedSheetLayout::TITLE_CHAR_WIDTH_MM
        );
        $optionLines = $this->wrapText(
            $this->buildOptionSummary($variantAnswers),
            $innerWidthMm,
            UnifiedSheetLayout::OPTION_CHAR_WIDTH_MM
        );

        $titleLineCount = max(1, count($titleLines));
        $optionLineCount = max(1, count($optionLines));
        $answerCount = max(1, $variantAnswers->count());
        $cellStepMm = UnifiedSheetLayout::CHOICE_BOX_SIZE_MM
            + UnifiedSheetLayout::CHOICE_CELL_GAP_MM
            + UnifiedSheetLayout::CHOICE_CELL_LABEL_GAP_MM;
        $availableWidthMm = max(
            UnifiedSheetLayout::CHOICE_BOX_SIZE_MM,
            $blockWidthMm
                - (UnifiedSheetLayout::QUESTION_INNER_PADDING_MM * 2)
                - UnifiedSheetLayout::ANSWER_LABEL_WIDTH_MM
                - UnifiedSheetLayout::ANSWER_GAP_MM
        );
        $cellsPerRow = max(
            1,
            min(
                UnifiedSheetLayout::CHOICE_MAX_PER_ROW,
                (int) floor(($availableWidthMm + UnifiedSheetLayout::CHOICE_CELL_GAP_MM) / $cellStepMm)
            )
        );
        $cellRowCount = max(1, (int) ceil($answerCount / $cellsPerRow));
        $firstAnswerRowHeightMm = max(
            UnifiedSheetLayout::LABEL_LINE_HEIGHT_MM,
            UnifiedSheetLayout::CHOICE_BOX_SIZE_MM
        );
        $answerAreaHeightMm = $firstAnswerRowHeightMm
            + (max(0, $cellRowCount - 1) * (UnifiedSheetLayout::CHOICE_BOX_SIZE_MM + UnifiedSheetLayout::CHOICE_ROW_GAP_MM));
        $blockHeightMm = (UnifiedSheetLayout::QUESTION_INNER_PADDING_MM * 2)
            + ($titleLineCount * UnifiedSheetLayout::TITLE_LINE_HEIGHT_MM)
            + UnifiedSheetLayout::TITLE_TO_OPTIONS_GAP_MM
            + ($optionLineCount * UnifiedSheetLayout::OPTION_LINE_HEIGHT_MM)
            + UnifiedSheetLayout::OPTIONS_TO_LABEL_GAP_MM
            + $answerAreaHeightMm
            + UnifiedSheetLayout::BOTTOM_BUFFER_MM;
        $answerRowTopMm = $topMm
            + UnifiedSheetLayout::QUESTION_INNER_PADDING_MM
            + ($titleLineCount * UnifiedSheetLayout::TITLE_LINE_HEIGHT_MM)
            + UnifiedSheetLayout::TITLE_TO_OPTIONS_GAP_MM
            + ($optionLineCount * UnifiedSheetLayout::OPTION_LINE_HEIGHT_MM)
            + UnifiedSheetLayout::OPTIONS_TO_LABEL_GAP_MM;
        $cellsTopMm = $answerRowTopMm + max(
            0,
            ($firstAnswerRowHeightMm - UnifiedSheetLayout::CHOICE_BOX_SIZE_MM) / 2
        );
        $cellOriginX = $blockLeftMm
            + UnifiedSheetLayout::QUESTION_INNER_PADDING_MM
            + UnifiedSheetLayout::ANSWER_LABEL_WIDTH_MM
            + UnifiedSheetLayout::ANSWER_GAP_MM;
        $cells = [];

        foreach ($variantAnswers->values() as $answerIndex => $answer) {
            $row = intdiv($answerIndex, $cellsPerRow);
            $column = $answerIndex % $cellsPerRow;
            $leftMm = $cellOriginX + ($column * $cellStepMm);
            $cellTopMm = $cellsTopMm + ($row * (UnifiedSheetLayout::CHOICE_BOX_SIZE_MM + UnifiedSheetLayout::CHOICE_ROW_GAP_MM));
            $letter = UnifiedSheetLayout::answerLetters()[$answerIndex] ?? (string) ($answerIndex + 1);

            $cells[] = [
                'answer_id' => (int) $answer->id,
                'option_index' => $answerIndex,
                'option_letter' => $letter,
                'answer_text' => (string) $answer->answer_text,
                'left_mm' => round($leftMm, 2),
                'top_mm' => round($cellTopMm, 2),
                'width_mm' => UnifiedSheetLayout::CHOICE_BOX_SIZE_MM,
                'height_mm' => UnifiedSheetLayout::CHOICE_BOX_SIZE_MM,
            ];
        }

        return [
            'question_id' => (int) $question->id,
            'question_number' => $questionNumber,
            'type' => (string) $question->type,
            'points' => (int) $question->points,
            'question_text' => (string) $question->question_text,
            'title_lines' => $titleLines,
            'option_lines' => $optionLines,
            'block' => [
                'left_mm' => round($blockLeftMm, 2),
                'top_mm' => round($topMm, 2),
                'width_mm' => round($blockWidthMm, 2),
                'height_mm' => round($blockHeightMm, 2),
            ],
            'cells_label' => 'Ответ:',
            'cells_label_left_mm' => round($blockLeftMm + UnifiedSheetLayout::QUESTION_INNER_PADDING_MM, 2),
            'cells_label_top_mm' => round($answerRowTopMm + max(
                0,
                ($firstAnswerRowHeightMm - UnifiedSheetLayout::LABEL_LINE_HEIGHT_MM) / 2
            ), 2),
            'cells' => $cells,
        ];
    }

    protected function buildEmptyQuestionPlaceholder(): array
    {
        return [
            'question_id' => 0,
            'question_number' => 0,
            'type' => 'single',
            'points' => 0,
            'question_text' => '',
            'title_lines' => ['В этом варианте нет вопросов.'],
            'option_lines' => [''],
            'block' => [
                'left_mm' => UnifiedSheetLayout::columnLeftMm(0),
                'top_mm' => UnifiedSheetLayout::questionAreaTopMm(),
                'width_mm' => UnifiedSheetLayout::columnWidthMm(),
                'height_mm' => 26.0,
            ],
            'cells_label' => '',
            'cells_label_left_mm' => 0,
            'cells_label_top_mm' => 0,
            'cells' => [],
            'is_placeholder' => true,
        ];
    }

    protected function buildOptionSummary(Collection $variantAnswers): string
    {
        return $variantAnswers
            ->values()
            ->map(function ($answer, int $index) {
                $letter = UnifiedSheetLayout::answerLetters()[$index] ?? (string) ($index + 1);

                return '(' . $letter . ') ' . trim((string) $answer->answer_text);
            })
            ->implode(' ');
    }

    protected function wrapText(string $text, float $widthMm, float $charWidthMm): array
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($normalized === '') {
            return [''];
        }

        $limit = max(
            12,
            (int) floor($widthMm / max($charWidthMm, 0.1)) - UnifiedSheetLayout::WRAP_SAFETY_CHARS
        );
        $words = preg_split('/\s+/u', $normalized) ?: [];
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;

            if (mb_strlen($candidate) <= $limit) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $lines[] = $current;
                $current = '';
            }

            while (mb_strlen($word) > $limit) {
                $lines[] = mb_substr($word, 0, $limit);
                $word = mb_substr($word, $limit);
            }

            $current = $word;
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return array_values(array_filter($lines, fn ($line) => trim((string) $line) !== ''));
    }
}
