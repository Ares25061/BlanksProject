<?php

namespace App\Services;

use App\Models\Test;
use App\Support\BlankScanLayout;

class TestPrintLayoutService
{
    private const MAX_LINES_PER_PAGE = 46;
    private const QUESTION_TEXT_CHARS_PER_LINE = 82;
    private const ANSWER_TEXT_CHARS_PER_LINE = 72;
    private const QUESTION_BLOCK_BASE_LINES = 3;
    private const QUESTION_BLOCK_EXTRA_LINES = 1;

    public function paginateAnswerSheetQuestions(Test $test, int $variantNumber = 1): array
    {
        $questions = $this->testVariantService->questionsForVariant($test, $variantNumber)->values();
        $totalQuestions = $questions->count();
        $pageCount = BlankScanLayout::questionPageCount($totalQuestions);
        $pages = [];

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $startIndex = BlankScanLayout::questionStartIndexForPage($pageNumber);
            $pageQuestions = $questions
                ->slice($startIndex, BlankScanLayout::questionsPerPage())
                ->values();

            $pages[] = [
                'page_number' => $pageNumber,
                'page_count' => $pageCount,
                'start_question_number' => $startIndex + 1,
                'end_question_number' => $startIndex + $pageQuestions->count(),
                'questions' => $pageQuestions,
            ];
        }

        return $pages;
    }

    public function __construct(
        private TestVariantService $testVariantService,
    ) {
    }

    public function paginateQuestions(Test $test, int $variantNumber = 1): array
    {
        $pages = [];
        $currentPage = [];
        $currentLines = 0;

        foreach ($this->testVariantService->questionsForVariant($test, $variantNumber)->values() as $index => $question) {
            $questionPayload = [
                'number' => $index + 1,
                'question' => $question,
                'variant_answers' => $this->testVariantService->orderedAnswersForQuestion($question, $variantNumber),
                'estimated_lines' => $this->estimateQuestionLines($question),
            ];

            $questionLines = $questionPayload['estimated_lines'];

            if ($currentPage !== [] && ($currentLines + $questionLines) > self::MAX_LINES_PER_PAGE) {
                $pages[] = $currentPage;
                $currentPage = [];
                $currentLines = 0;
            }

            $currentPage[] = $questionPayload;
            $currentLines += $questionLines;
        }

        if ($currentPage !== []) {
            $pages[] = $currentPage;
        }

        return $pages;
    }

    private function estimateQuestionLines($question): int
    {
        $lines = self::QUESTION_BLOCK_BASE_LINES
            + max(1, (int) ceil(mb_strlen((string) $question->question_text) / self::QUESTION_TEXT_CHARS_PER_LINE));

        foreach ($question->answers as $answer) {
            $lines += max(1, (int) ceil(mb_strlen((string) $answer->answer_text) / self::ANSWER_TEXT_CHARS_PER_LINE));
        }

        return max(6, $lines + self::QUESTION_BLOCK_EXTRA_LINES);
    }
}
