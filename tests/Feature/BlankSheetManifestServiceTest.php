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
        $this->assertSame('unified-sheet-v2', data_get($blankForm->metadata, 'print_layout.version'));
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
}
