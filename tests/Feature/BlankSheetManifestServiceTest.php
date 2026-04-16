<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\BlankForm;
use App\Models\Question;
use App\Models\Test;
use App\Models\User;
use App\Services\BlankSheetManifestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlankSheetManifestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_service_persists_unified_page_snapshot_for_blank_form(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create();

        $test = Test::create([
            'title' => 'Unified print',
            'subject_name' => 'Programming',
            'created_by' => $teacher->id,
            'is_active' => true,
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 0],
            ],
        ]);

        $question = Question::create([
            'test_id' => $test->id,
            'question_text' => 'Выберите правильный ответ',
            'type' => 'single',
            'points' => 1,
            'order' => 0,
        ]);

        $answerA = Answer::create([
            'question_id' => $question->id,
            'answer_text' => 'Вариант A',
            'is_correct' => true,
            'order' => 0,
        ]);

        Answer::create([
            'question_id' => $question->id,
            'answer_text' => 'Вариант B',
            'is_correct' => false,
            'order' => 1,
        ]);

        $blankForm = BlankForm::create([
            'test_id' => $test->id,
            'form_number' => 'TEST-UNIFIED-001',
            'variant_number' => 1,
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'group_name' => '25ИС1-7',
            'status' => 'generated',
        ]);

        $pages = app(BlankSheetManifestService::class)->ensurePersisted($blankForm->fresh('test.questions.answers'));
        $blankForm = $blankForm->fresh();

        $this->assertCount(1, $pages);
        $this->assertSame('unified-sheet-v11', data_get($blankForm->metadata, 'print_layout.version'));
        $this->assertSame(1, data_get($blankForm->metadata, 'print_layout.page_count'));

        $manifestPath = data_get($blankForm->metadata, 'print_layout.pages.0.manifest_path');
        Storage::disk('local')->assertExists($manifestPath);
        $this->assertSame($answerA->id, data_get($pages, '0.questions.0.cells.0.answer_id'));
        $this->assertSame('A', data_get($pages, '0.questions.0.cells.0.option_letter'));
        $this->assertSame(1, data_get($pages, '0.question_range.start'));
        $this->assertSame(1, data_get($pages, '0.question_range.end'));
        $this->assertSame(210.0, data_get($pages, '0.page_width_mm'));
        $this->assertNotEmpty(data_get($pages, '0.qr_payload.sig'));
    }

    public function test_manifest_layout_places_answer_label_inline_with_up_to_four_cells(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create();

        $test = Test::create([
            'title' => 'Inline answer row',
            'subject_name' => 'Programming',
            'created_by' => $teacher->id,
            'is_active' => true,
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 0],
            ],
        ]);

        $question = Question::create([
            'test_id' => $test->id,
            'question_text' => 'Выберите правильные варианты',
            'type' => 'multiple',
            'points' => 1,
            'order' => 0,
        ]);

        foreach (range(0, 3) as $index) {
            Answer::create([
                'question_id' => $question->id,
                'answer_text' => 'Вариант ' . chr(65 + $index),
                'is_correct' => $index < 2,
                'order' => $index,
            ]);
        }

        $blankForm = BlankForm::create([
            'test_id' => $test->id,
            'form_number' => 'TEST-UNIFIED-005',
            'variant_number' => 1,
            'last_name' => 'Петров',
            'first_name' => 'Петр',
            'group_name' => '25ИС1-7',
            'status' => 'generated',
        ]);

        $pages = app(BlankSheetManifestService::class)->ensurePersisted($blankForm->fresh('test.questions.answers'));
        $cells = data_get($pages, '0.questions.0.cells', []);
        $cellTops = collect($cells)->pluck('top_mm')->unique()->values()->all();
        $labelTop = (float) data_get($pages, '0.questions.0.cells_label_top_mm');
        $firstCellTop = (float) data_get($cells, '0.top_mm');

        $this->assertSame('Ответ:', data_get($pages, '0.questions.0.cells_label'));
        $this->assertCount(4, $cells);
        $this->assertCount(1, $cellTops);
        $this->assertLessThan(1.0, abs($labelTop - $firstCellTop));
    }

    public function test_manifest_layout_pushes_answer_row_below_wrapped_text_content(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create();

        $test = Test::create([
            'title' => 'Adaptive cards',
            'subject_name' => 'Programming',
            'created_by' => $teacher->id,
            'is_active' => true,
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 0],
            ],
        ]);

        $question = Question::create([
            'test_id' => $test->id,
            'question_text' => 'Как называется именованная область памяти?',
            'type' => 'single',
            'points' => 1,
            'order' => 0,
        ]);

        foreach (['Класс', 'Переменная', 'Цикл', 'Функция'] as $index => $answerText) {
            Answer::create([
                'question_id' => $question->id,
                'answer_text' => $answerText,
                'is_correct' => $answerText === 'Переменная',
                'order' => $index,
            ]);
        }

        $blankForm = BlankForm::create([
            'test_id' => $test->id,
            'form_number' => 'TEST-ADAPTIVE-001',
            'variant_number' => 1,
            'last_name' => 'Смирнов',
            'first_name' => 'Илья',
            'group_name' => '25ИС1-7',
            'status' => 'generated',
        ]);

        $pages = app(BlankSheetManifestService::class)->ensurePersisted($blankForm->fresh('test.questions.answers'));
        $questionPayload = data_get($pages, '0.questions.0');
        $blockTop = (float) data_get($questionPayload, 'block.top_mm');
        $labelTop = (float) data_get($questionPayload, 'cells_label_top_mm');
        $contentBottom = $blockTop
            + \App\Support\UnifiedSheetLayout::QUESTION_INNER_PADDING_MM
            + (count($questionPayload['title_lines'] ?? []) * \App\Support\UnifiedSheetLayout::TITLE_LINE_HEIGHT_MM)
            + \App\Support\UnifiedSheetLayout::TITLE_TO_OPTIONS_GAP_MM
            + (count($questionPayload['option_lines'] ?? []) * \App\Support\UnifiedSheetLayout::OPTION_LINE_HEIGHT_MM);

        $this->assertGreaterThanOrEqual(2, count($questionPayload['title_lines'] ?? []));
        $this->assertGreaterThan(
            $contentBottom + 1.0,
            $labelTop
        );
    }
}
