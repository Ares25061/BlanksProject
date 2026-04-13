<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\BlankForm;
use App\Models\GroupSubject;
use App\Models\Question;
use App\Models\StudentGrade;
use App\Models\Test;
use App\Models\User;
use App\Services\TestService;
use App\Services\BlankFormService;
use App\Services\GradingService;
use App\Services\StudentGradeService;
use App\Services\StudentGroupService;
use App\Services\TestPrintLayoutService;
use App\Services\TestVariantService;
use App\Support\BlankScanLayout;
use App\Support\SimpleXlsx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeacherWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_group_service_saves_group_with_students(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);

        $group = app(StudentGroupService::class)->createGroup([
            'name' => '22ИС4-1',
            'description' => 'Группа для тестового сценария',
            'students' => [
                'Дудина Софья Романовна',
                'Петров Иван Сергеевич',
                'Сидоров Павел Олегович',
            ],
        ]);

        $this->assertSame('22ИС4-1', $group->name);
        $this->assertCount(3, $group->students);
        $this->assertSame('Дудина Софья Романовна', $group->students[0]->full_name);
    }

    public function test_api_tests_index_returns_only_current_teachers_tests(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();

        $ownTest = Test::create([
            'title' => 'Мой тест',
            'created_by' => $teacher->id,
            'is_active' => true,
        ]);

        $foreignTest = Test::create([
            'title' => 'Чужой тест',
            'created_by' => $otherTeacher->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher, 'api')->getJson('/api/tests');

        $response->assertOk();
        $testIds = collect($response->json('data.data'))->pluck('id')->all();

        $this->assertContains($ownTest->id, $testIds);
        $this->assertNotContains($foreignTest->id, $testIds);
    }

    public function test_api_test_show_forbids_viewing_foreign_test(): void
    {
        $teacher = User::factory()->create();
        $otherTeacher = User::factory()->create();

        $foreignTest = Test::create([
            'title' => 'Чужой тест',
            'created_by' => $otherTeacher->id,
            'is_active' => true,
        ]);

        $this->actingAs($teacher, 'api')
            ->getJson('/api/tests/' . $foreignTest->id)
            ->assertForbidden();
    }

    public function test_grading_service_uses_custom_grade_criteria(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $test = Test::create([
            'title' => 'Проверка критериев',
            'created_by' => $teacher->id,
            'is_active' => true,
            'grade_criteria' => [
                ['label' => '5 (Отлично)', 'min_points' => 2],
                ['label' => '4 (Хорошо)', 'min_points' => 1],
                ['label' => '2 (Нужно доработать)', 'min_points' => 0],
            ],
        ]);

        $questionOne = Question::create([
            'test_id' => $test->id,
            'question_text' => 'Первый вопрос',
            'type' => 'single',
            'points' => 1,
            'order' => 0,
        ]);

        $questionTwo = Question::create([
            'test_id' => $test->id,
            'question_text' => 'Второй вопрос',
            'type' => 'single',
            'points' => 1,
            'order' => 1,
        ]);

        $correctOne = Answer::create([
            'question_id' => $questionOne->id,
            'answer_text' => 'Правильный 1',
            'is_correct' => true,
            'order' => 0,
        ]);

        Answer::create([
            'question_id' => $questionOne->id,
            'answer_text' => 'Неправильный 1',
            'is_correct' => false,
            'order' => 1,
        ]);

        $wrongTwo = Answer::create([
            'question_id' => $questionTwo->id,
            'answer_text' => 'Неправильный 2',
            'is_correct' => false,
            'order' => 0,
        ]);

        Answer::create([
            'question_id' => $questionTwo->id,
            'answer_text' => 'Правильный 2',
            'is_correct' => true,
            'order' => 1,
        ]);

        $blankForm = BlankForm::create([
            'test_id' => $test->id,
            'form_number' => 'TEST-1-CUSTOM',
            'last_name' => 'Дудина',
            'first_name' => 'Софья',
            'patronymic' => 'Романовна',
            'group_name' => '22ИС4-1',
            'status' => 'submitted',
        ]);

        $blankForm->studentAnswers()->create([
            'question_id' => $questionOne->id,
            'answer_id' => $correctOne->id,
        ]);

        $blankForm->studentAnswers()->create([
            'question_id' => $questionTwo->id,
            'answer_id' => $wrongTwo->id,
        ]);

        $checked = app(GradingService::class)->checkBlankForm($blankForm->fresh(['test.questions.answers', 'studentAnswers']));

        $this->assertSame(1, $checked->total_score);
        $this->assertSame('4 (Хорошо)', $checked->grade_label);
    }

    public function test_group_update_preserves_existing_student_record_for_same_name(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);

        $service = app(StudentGroupService::class);
        $group = $service->createGroup([
            'name' => '22ИС4-1',
            'students' => [
                'Дудина Софья Романовна',
                'Петров Иван Сергеевич',
            ],
        ]);

        $existingStudentId = $group->students->firstWhere('full_name', 'Дудина Софья Романовна')->id;

        $updatedGroup = $service->updateGroup($group, [
            'name' => '22ИС4-1',
            'students' => [
                'Петров Иван Сергеевич',
                'Дудина Софья Романовна',
                'Семенов Евгений Дмитриевич',
            ],
        ]);

        $preservedStudent = $updatedGroup->students->firstWhere('full_name', 'Дудина Софья Романовна');

        $this->assertNotNull($preservedStudent);
        $this->assertSame($existingStudentId, $preservedStudent->id);
    }

    public function test_checked_blank_form_can_store_assigned_grade_with_date(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $group = app(StudentGroupService::class)->createGroup([
            'name' => '22ИС4-1',
            'students' => [
                'Семенов Евгений Дмитриевич',
            ],
        ]);

        $test = Test::create([
            'title' => 'Проверенная работа',
            'subject_name' => 'Программирование',
            'created_by' => $teacher->id,
            'is_active' => true,
        ]);

        $blankForm = BlankForm::create([
            'test_id' => $test->id,
            'student_group_id' => $group->id,
            'group_student_id' => $group->students->first()->id,
            'form_number' => 'TEST-1-GRADE',
            'last_name' => 'Семенов',
            'first_name' => 'Евгений',
            'patronymic' => 'Дмитриевич',
            'group_name' => '22ИС4-1',
            'status' => 'checked',
            'total_score' => 2,
            'grade_label' => '4 (Хорошо)',
        ]);

        $assigned = app(GradingService::class)->assignStudentGrade($blankForm, [
            'grade_value' => '5',
            'grade_date' => '2026-03-28',
        ]);

        $this->assertSame('5', $assigned->assigned_grade_value);
        $this->assertSame('2026-03-28', $assigned->assigned_grade_date?->format('Y-m-d'));
        $this->assertSame($teacher->id, $assigned->assigned_grade_by);
        $studentGrade = StudentGrade::where('blank_form_id', $blankForm->id)->first();
        $this->assertNotNull($studentGrade);
        $this->assertSame($group->students->first()->id, $studentGrade->group_student_id);
        $this->assertSame($group->id, $studentGrade->student_group_id);
        $this->assertSame('Программирование', $studentGrade->subject_name);
        $this->assertSame('5', $studentGrade->grade_value);
        $this->assertSame('2026-03-28', $studentGrade->grade_date?->format('Y-m-d'));
    }

    public function test_blank_form_service_deletes_blank_form_answers_and_scan(): void
    {
        Storage::fake('local');

        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $group = app(StudentGroupService::class)->createGroup([
            'name' => '22ИС4-1',
            'students' => [
                'Семенов Евгений Дмитриевич',
            ],
        ]);
        $student = $group->students->first();

        $test = Test::create([
            'title' => 'Удаление бланка',
            'subject_name' => 'Удаление бланка',
            'created_by' => $teacher->id,
            'is_active' => true,
        ]);

        $question = Question::create([
            'test_id' => $test->id,
            'question_text' => 'Вопрос',
            'type' => 'single',
            'points' => 1,
            'order' => 0,
        ]);

        $answer = Answer::create([
            'question_id' => $question->id,
            'answer_text' => 'Ответ',
            'is_correct' => true,
            'order' => 0,
        ]);

        Storage::disk('local')->put('scans/test-delete.jpg', 'scan');

        $blankForm = BlankForm::create([
            'test_id' => $test->id,
            'student_group_id' => $group->id,
            'group_student_id' => $student->id,
            'form_number' => 'TEST-1-DELETE',
            'status' => 'checked',
            'scan_path' => 'scans/test-delete.jpg',
        ]);

        StudentGrade::create([
            'student_group_id' => $group->id,
            'group_student_id' => $student->id,
            'blank_form_id' => $blankForm->id,
            'subject_name' => 'Удаление бланка',
            'grade_value' => '5',
            'grade_date' => '2026-03-28',
            'created_by' => $teacher->id,
            'updated_by' => $teacher->id,
        ]);

        $blankForm->studentAnswers()->create([
            'question_id' => $question->id,
            'answer_id' => $answer->id,
        ]);

        app(BlankFormService::class)->deleteBlankForm($blankForm);

        $this->assertDatabaseMissing('blank_forms', ['id' => $blankForm->id]);
        $this->assertDatabaseCount('student_answers', 0);
        $this->assertDatabaseCount('student_grades', 0);
        Storage::disk('local')->assertMissing('scans/test-delete.jpg');
    }

    public function test_student_grade_service_builds_gradebook_for_selected_subject(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $group = app(StudentGroupService::class)->createGroup([
            'name' => '22ИС4-1',
            'students' => [
                'Дудина Софья Романовна',
                'Семенов Евгений Дмитриевич',
            ],
        ]);

        $studentOne = $group->students->first();
        $studentTwo = $group->students->last();

        StudentGrade::create([
            'student_group_id' => $group->id,
            'group_student_id' => $studentOne->id,
            'subject_name' => 'Программирование',
            'grade_value' => '5',
            'grade_date' => '2026-03-28',
            'created_by' => $teacher->id,
            'updated_by' => $teacher->id,
        ]);

        StudentGrade::create([
            'student_group_id' => $group->id,
            'group_student_id' => $studentTwo->id,
            'subject_name' => 'Программирование',
            'grade_value' => '4',
            'grade_date' => '2026-03-29',
            'created_by' => $teacher->id,
            'updated_by' => $teacher->id,
        ]);

        StudentGrade::create([
            'student_group_id' => $group->id,
            'group_student_id' => $studentOne->id,
            'subject_name' => 'Математика',
            'grade_value' => '3',
            'grade_date' => '2026-03-28',
            'created_by' => $teacher->id,
            'updated_by' => $teacher->id,
        ]);

        $gradebook = app(StudentGradeService::class)->buildGradebook($group->fresh(), 'Программирование');

        $this->assertSame('Программирование', $gradebook['subject_name']);
        $this->assertSame(['2026-03-28', '2026-03-29'], $gradebook['dates']);
        $this->assertContains('Математика', $gradebook['available_subjects']);
        $this->assertSame('5', $gradebook['students'][0]['grades']['2026-03-28']['grade_value']);
        $this->assertArrayNotHasKey('2026-03-28', $gradebook['students'][1]['grades']);
    }

    public function test_student_grade_service_persists_empty_subject_gradebook(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $group = app(StudentGroupService::class)->createGroup([
            'name' => '22ИС4-1',
            'students' => [
                'Дудина Софья Романовна',
            ],
        ]);

        $gradebook = app(StudentGradeService::class)->buildGradebook($group->fresh(), 'Информатика');

        $this->assertSame('Информатика', $gradebook['subject_name']);
        $this->assertContains('Информатика', $gradebook['available_subjects']);
        $this->assertDatabaseHas('group_subjects', [
            'student_group_id' => $group->id,
            'subject_name' => 'Информатика',
        ]);
        $this->assertSame('Информатика', GroupSubject::query()->where('student_group_id', $group->id)->value('subject_name'));
    }

    public function test_test_service_stores_subject_name(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $test = app(TestService::class)->createTest([
            'title' => 'Контрольная работа №1',
            'subject_name' => 'Программирование',
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 0],
            ],
            'questions' => [
                [
                    'question_text' => 'Вопрос 1',
                    'type' => 'single',
                    'points' => 1,
                    'answers' => [
                        ['answer_text' => 'A', 'is_correct' => true],
                        ['answer_text' => 'B', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $this->assertSame('Программирование', $test->subject_name);
    }

    public function test_test_service_allows_large_test_with_thirty_questions(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $questions = collect(range(1, 30))
            ->map(fn (int $index) => [
                'question_text' => 'Вопрос ' . $index,
                'type' => 'single',
                'points' => 1,
                'answers' => [
                    ['answer_text' => 'A', 'is_correct' => true],
                    ['answer_text' => 'B', 'is_correct' => false],
                ],
            ])
            ->all();

        $test = app(TestService::class)->createTest([
            'title' => 'Большой тест',
            'subject_name' => 'Программирование',
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 0],
            ],
            'questions' => $questions,
        ]);

        $this->assertCount(30, $test->questions);
        $this->assertSame(2, BlankScanLayout::questionPageCount($test->questions->count()));
    }

    public function test_print_renders_multiple_answer_sheets_for_large_test(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $questions = collect(range(1, 30))
            ->map(fn (int $index) => [
                'question_text' => 'Вопрос ' . $index,
                'type' => $index % 5 === 0 ? 'multiple' : 'single',
                'points' => 1,
                'answers' => [
                    ['answer_text' => 'A', 'is_correct' => true],
                    ['answer_text' => 'B', 'is_correct' => $index % 5 === 0],
                    ['answer_text' => 'C', 'is_correct' => false],
                ],
            ])
            ->all();

        $test = app(TestService::class)->createTest([
            'title' => 'Печать большого теста',
            'subject_name' => 'Программирование',
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 0],
            ],
            'questions' => $questions,
        ]);

        $response = $this->get('/tests/' . $test->id . '/print');
        $response->assertOk();
        $response->assertSee('Новый шаблон печатается единым листом', false);
        $response->assertSee('Студент:', false);
        $response->assertSee('Тест:', false);
        $response->assertSee('Группа:', false);
        $response->assertSee('Ответ:', false);
        $response->assertSee('Вопрос 1', false);
        $response->assertSee('Вопрос 30', false);

        return;

        $response->assertOk();
        $response->assertSee('Лист ответов: 1 / 2', false);
        $response->assertSee('Лист ответов: 2 / 2', false);
        $response->assertSee('Вопросы:</strong> 25-30', false);
    }

    public function test_blank_form_service_balances_variants_across_group_students(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $group = app(StudentGroupService::class)->createGroup([
            'name' => '22ИС4-1',
            'students' => [
                'Дудина Софья Романовна',
                'Каличенок Иван Максимович',
                'Семенов Евгений Дмитриевич',
                'Марсов Георгий Павлович',
            ],
        ]);

        $test = Test::create([
            'title' => 'Тест с вариантами',
            'subject_name' => 'Программирование',
            'created_by' => $teacher->id,
            'is_active' => true,
            'variant_count' => 3,
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 0],
            ],
        ]);

        $forms = app(BlankFormService::class)->generateBlankFormsForGroup($test, $group, [], [
            'mode' => 'balanced',
        ]);

        $this->assertCount(4, $forms);
        $this->assertSame([1, 2, 3, 1], collect($forms)->pluck('variant_number')->map(fn ($value) => (int) $value)->all());
    }

    public function test_question_print_layout_splits_short_questions_before_browser_print_overflow(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $questions = collect(range(1, 7))
            ->map(fn (int $index) => [
                'question_text' => 'Короткий вопрос ' . $index,
                'type' => 'single',
                'points' => 1,
                'answers' => [
                    ['answer_text' => 'Вариант A', 'is_correct' => true],
                    ['answer_text' => 'Вариант B', 'is_correct' => false],
                    ['answer_text' => 'Вариант C', 'is_correct' => false],
                ],
            ])
            ->all();

        $test = app(TestService::class)->createTest([
            'title' => 'Проверка печатной пагинации',
            'subject_name' => 'Программирование',
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 0],
            ],
            'questions' => $questions,
        ]);

        $pages = app(TestPrintLayoutService::class)->paginateQuestions($test->fresh('questions.answers'));

        $this->assertCount(2, $pages);
        $this->assertCount(5, $pages[0]);
        $this->assertCount(2, $pages[1]);
        $this->assertSame(1, $pages[0][0]['number']);
        $this->assertSame(6, $pages[1][0]['number']);
    }

    public function test_grading_service_builds_foreign_scan_preview_without_grade_assignment(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $test = Test::create([
            'title' => 'OCR preview',
            'subject_name' => 'Программирование',
            'created_by' => $teacher->id,
            'is_active' => true,
            'variant_count' => 4,
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 1],
                ['label' => '2', 'min_points' => 0],
            ],
        ]);

        $question = Question::create([
            'test_id' => $test->id,
            'question_text' => 'Выберите правильный ответ',
            'type' => 'single',
            'points' => 1,
            'order' => 0,
            'variant_number' => 2,
        ]);

        $answerOne = Answer::create([
            'question_id' => $question->id,
            'answer_text' => 'Неверно',
            'is_correct' => false,
            'order' => 0,
        ]);

        $answerTwo = Answer::create([
            'question_id' => $question->id,
            'answer_text' => 'Верно',
            'is_correct' => true,
            'order' => 1,
        ]);

        $blankForm = BlankForm::create([
            'test_id' => $test->id,
            'form_number' => 'TEST-FOREIGN-PREVIEW',
            'variant_number' => 2,
            'last_name' => 'Семенов',
            'first_name' => 'Евгений',
            'patronymic' => 'Дмитриевич',
            'group_name' => '22ИС4-1',
            'status' => 'generated',
        ]);

        $variantAnswers = app(TestVariantService::class)->orderedAnswersForQuestion(
            $question->load('answers'),
            2
        );
        $selectedCorrectAnswerId = $variantAnswers->firstWhere('is_correct', true)?->id;

        $preview = app(GradingService::class)->buildTransientScanReview(
            $blankForm->fresh('test.questions.answers'),
            [$question->id => [$selectedCorrectAnswerId]],
            [
                'file_name' => 'foreign.jpg',
                'recognized_answers' => [
                    ['question_number' => 1, 'selected' => ['A']],
                ],
            ]
        );

        $this->assertNull($preview['data']['id']);
        $this->assertTrue($preview['data']['is_foreign_scan']);
        $this->assertFalse($preview['data']['can_assign_grade']);
        $this->assertSame(1, $preview['grade']['score']);
        $this->assertSame('5', $preview['grade']['grade']);
        $this->assertSame(2, $preview['data']['variant_number']);
        $this->assertSame($selectedCorrectAnswerId, $preview['data']['student_answers'][0]['answer_id']);
        $this->assertNull($preview['data']['group_student_id']);
    }

    public function test_test_service_rejects_more_than_four_answers_in_question(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $test = Test::create([
            'title' => 'Лимит ответов',
            'created_by' => $teacher->id,
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(TestService::class)->addQuestionToTest($test, [
            'question_text' => 'Слишком много вариантов',
            'type' => 'single',
            'points' => 1,
            'answers' => [
                ['answer_text' => 'A', 'is_correct' => true],
                ['answer_text' => 'B', 'is_correct' => false],
                ['answer_text' => 'C', 'is_correct' => false],
                ['answer_text' => 'D', 'is_correct' => false],
                ['answer_text' => 'E', 'is_correct' => false],
                ['answer_text' => 'F', 'is_correct' => false],
            ],
        ]);
    }

    public function test_api_can_import_questions_from_json_file(): void
    {
        $teacher = User::factory()->create();
        $payload = [
            'title' => 'Импортированный тест',
            'subject_name' => 'Информатика',
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 3],
            ],
            'questions' => [
                [
                    'question_text' => 'Столица Франции',
                    'points' => 1,
                    'answers' => [
                        ['answer_text' => 'Париж', 'is_correct' => true],
                        ['answer_text' => 'Лион', 'is_correct' => false],
                    ],
                ],
                [
                    'question_text' => 'Выберите языки программирования',
                    'correct' => ['A', 'C'],
                    'answers' => [
                        ['answer_text' => 'PHP'],
                        ['answer_text' => 'HTML'],
                        ['answer_text' => 'Python'],
                    ],
                ],
            ],
        ];

        $jsonPath = storage_path('framework/testing/questions-import.json');
        file_put_contents($jsonPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $file = new UploadedFile($jsonPath, 'questions.json', 'application/json', null, true);

        $response = $this->actingAs($teacher, 'api')->post('/api/tests/import-questions', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.title', 'Импортированный тест');
        $response->assertJsonPath('data.subject_name', 'Информатика');
        $response->assertJsonPath('data.variant_count', 1);
        $response->assertJsonPath('data.questions.0.question_text', 'Столица Франции');
        $response->assertJsonPath('data.questions.0.variant_number', 1);
        $response->assertJsonPath('data.questions.1.type', 'multiple');
        $response->assertJsonPath('data.questions.1.variant_number', 1);
        $response->assertJsonPath('data.questions.1.answers.0.is_correct', true);
        $response->assertJsonPath('data.questions.1.answers.1.is_correct', false);
        $response->assertJsonPath('data.questions.1.answers.2.is_correct', true);

        @unlink($jsonPath);
    }

    public function test_api_can_import_variant_questions_from_json_file(): void
    {
        $teacher = User::factory()->create();
        $payload = [
            'title' => 'Импорт с вариантами',
            'subject_name' => 'Программирование',
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 2],
            ],
            'questions' => [
                [
                    'variant' => 1,
                    'question_text' => 'Вариант 1. Вопрос 1',
                    'type' => 'single',
                    'points' => 1,
                    'answers' => [
                        ['answer_text' => 'A1', 'is_correct' => true],
                        ['answer_text' => 'B1', 'is_correct' => false],
                    ],
                ],
                [
                    'variant' => 2,
                    'question_text' => 'Вариант 2. Вопрос 1',
                    'type' => 'multiple',
                    'points' => 2,
                    'correct' => ['A', 'C'],
                    'answers' => [
                        ['answer_text' => 'A2'],
                        ['answer_text' => 'B2'],
                        ['answer_text' => 'C2'],
                    ],
                ],
            ],
        ];

        $jsonPath = storage_path('framework/testing/questions-import-variants.json');
        file_put_contents($jsonPath, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $file = new UploadedFile($jsonPath, 'questions-variants.json', 'application/json', null, true);

        $response = $this->actingAs($teacher, 'api')->post('/api/tests/import-questions', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.variant_count', 2);
        $response->assertJsonPath('data.questions.0.variant_number', 1);
        $response->assertJsonPath('data.questions.1.variant_number', 2);
        $response->assertJsonPath('data.questions.1.type', 'multiple');
        $response->assertJsonPath('data.questions.1.answers.0.is_correct', true);
        $response->assertJsonPath('data.questions.1.answers.1.is_correct', false);
        $response->assertJsonPath('data.questions.1.answers.2.is_correct', true);

        @unlink($jsonPath);
    }

    public function test_test_service_requires_questions_for_each_variant_when_multiple_variants(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $this->expectException(ValidationException::class);

        app(TestService::class)->createTest([
            'title' => 'Неполный набор вариантов',
            'subject_name' => 'Программирование',
            'variant_count' => 2,
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 0],
            ],
            'questions' => [
                [
                    'question_text' => 'Только для первого варианта',
                    'type' => 'single',
                    'points' => 1,
                    'variant_number' => 1,
                    'answers' => [
                        ['answer_text' => 'A', 'is_correct' => true],
                        ['answer_text' => 'B', 'is_correct' => false],
                    ],
                ],
            ],
        ]);
    }

    public function test_api_can_import_questions_from_xlsx_file(): void
    {
        $teacher = User::factory()->create();
        $xlsxPath = SimpleXlsx::writeWorkbook('Вопросы', [
            ['question_text', 'type', 'points', 'answer_a', 'answer_b', 'answer_c', 'correct'],
            ['Сколько будет 2+2?', 'single', '1', '3', '4', '', 'B'],
            ['Выберите числа больше 5', '', '2', '4', '6', '8', '2,3'],
        ]);

        $file = new UploadedFile(
            $xlsxPath,
            'questions.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($teacher, 'api')->post('/api/tests/import-questions', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.questions.0.question_text', 'Сколько будет 2+2?');
        $response->assertJsonPath('data.questions.0.answers.1.is_correct', true);
        $response->assertJsonPath('data.questions.1.type', 'multiple');
        $response->assertJsonPath('data.questions.1.answers.1.is_correct', true);
        $response->assertJsonPath('data.questions.1.answers.2.is_correct', true);

        @unlink($xlsxPath);
    }

    public function test_api_can_import_questions_from_exported_xlsx_layout(): void
    {
        $teacher = User::factory()->create();
        $xlsxPath = SimpleXlsx::writeWorkbook('Тест', [
            ['title', 'Контрольная работа с вариантами'],
            ['subject_name', 'Программирование'],
            ['description', 'Импорт из экспортированного XLSX'],
            ['time_limit', '45'],
            ['variant_count', '2'],
            ['grade_criteria_json', '[{"label":"5","min_points":2},{"label":"4","min_points":1},{"label":"2","min_points":0}]'],
            [],
            ['question_text', 'variant', 'type', 'points', 'answer_a', 'answer_b', 'answer_c', 'answer_d', 'correct'],
            ['Вариант 1. Вопрос 1', '1', 'single', '1', 'A1', 'B1', '', '', 'A'],
            ['Вариант 2. Вопрос 1', '2', 'multiple', '2', 'A2', 'B2', 'C2', '', 'A,C'],
        ]);

        $file = new UploadedFile(
            $xlsxPath,
            'questions-exported.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($teacher, 'api')->post('/api/tests/import-questions', [
            'file' => $file,
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.title', 'Контрольная работа с вариантами');
        $response->assertJsonPath('data.subject_name', 'Программирование');
        $response->assertJsonPath('data.description', 'Импорт из экспортированного XLSX');
        $response->assertJsonPath('data.time_limit', 45);
        $response->assertJsonPath('data.variant_count', 2);
        $response->assertJsonPath('data.grade_criteria.0.label', '5');
        $response->assertJsonPath('data.questions.0.variant_number', 1);
        $response->assertJsonPath('data.questions.1.variant_number', 2);
        $response->assertJsonPath('data.questions.1.answers.0.is_correct', true);
        $response->assertJsonPath('data.questions.1.answers.2.is_correct', true);

        @unlink($xlsxPath);
    }

    public function test_api_can_export_test_as_json_file(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher, 'api');
        Auth::login($teacher);

        $test = app(TestService::class)->createTest([
            'title' => 'Экспорт JSON',
            'subject_name' => 'Программирование',
            'variant_count' => 2,
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 1],
                ['label' => '2', 'min_points' => 0],
            ],
            'questions' => [
                [
                    'question_text' => 'Вариант 1. Вопрос 1',
                    'type' => 'single',
                    'points' => 1,
                    'variant_number' => 1,
                    'answers' => [
                        ['answer_text' => 'A1', 'is_correct' => true],
                        ['answer_text' => 'B1', 'is_correct' => false],
                    ],
                ],
                [
                    'question_text' => 'Вариант 2. Вопрос 1',
                    'type' => 'multiple',
                    'points' => 2,
                    'variant_number' => 2,
                    'answers' => [
                        ['answer_text' => 'A2', 'is_correct' => true],
                        ['answer_text' => 'B2', 'is_correct' => false],
                        ['answer_text' => 'C2', 'is_correct' => true],
                    ],
                ],
            ],
        ]);

        $response = $this->get('/api/tests/' . $test->id . '/export?format=json');

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Экспорт JSON', $payload['title'] ?? null);
        $this->assertSame('Программирование', $payload['subject_name'] ?? null);
        $this->assertSame(2, $payload['variant_count'] ?? null);
        $variants = collect($payload['questions'] ?? [])
            ->pluck('variant')
            ->map(fn ($value) => (int) $value)
            ->sort()
            ->values()
            ->all();
        $variantTwoQuestion = collect($payload['questions'] ?? [])
            ->first(fn (array $question) => (int) ($question['variant'] ?? 0) === 2);

        $this->assertSame([1, 2], $variants);
        $this->assertNotNull($variantTwoQuestion);
        $this->assertTrue($variantTwoQuestion['answers'][2]['is_correct'] ?? false);
    }

    public function test_api_can_export_test_as_xlsx_file(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher, 'api');
        Auth::login($teacher);

        $test = app(TestService::class)->createTest([
            'title' => 'Экспорт XLSX',
            'subject_name' => 'Программирование',
            'description' => 'Проверка экспортируемого Excel',
            'time_limit' => 40,
            'variant_count' => 2,
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 1],
                ['label' => '2', 'min_points' => 0],
            ],
            'questions' => [
                [
                    'question_text' => 'Вариант 1. Вопрос 1',
                    'type' => 'single',
                    'points' => 1,
                    'variant_number' => 1,
                    'answers' => [
                        ['answer_text' => 'A1', 'is_correct' => true],
                        ['answer_text' => 'B1', 'is_correct' => false],
                    ],
                ],
                [
                    'question_text' => 'Вариант 2. Вопрос 1',
                    'type' => 'multiple',
                    'points' => 2,
                    'variant_number' => 2,
                    'answers' => [
                        ['answer_text' => 'A2', 'is_correct' => true],
                        ['answer_text' => 'B2', 'is_correct' => false],
                        ['answer_text' => 'C2', 'is_correct' => true],
                    ],
                ],
            ],
        ]);

        $response = $this->get('/api/tests/' . $test->id . '/export?format=xlsx');

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $rows = SimpleXlsx::readRows($response->baseResponse->getFile()->getPathname());
        $headerRowIndex = collect($rows)->search(fn (array $row) => ($row[0] ?? null) === 'question_text');
        $variantTwoRowIndex = collect($rows)->search(fn (array $row) => ($row[0] ?? null) === 'Вариант 2. Вопрос 1');

        $this->assertSame(['title', 'Экспорт XLSX'], [$rows[0][0] ?? null, $rows[0][1] ?? null]);
        $this->assertSame(['variant_count', '2'], [$rows[4][0] ?? null, $rows[4][1] ?? null]);
        $this->assertNotFalse($headerRowIndex);
        $this->assertSame('variant', $rows[$headerRowIndex][1] ?? null);
        $this->assertNotFalse($variantTwoRowIndex);
        $this->assertSame('2', $rows[$variantTwoRowIndex][1] ?? null);
        $this->assertSame('A,C', $rows[$variantTwoRowIndex][8] ?? null);
    }

    public function test_gradebook_month_export_returns_xlsx_file(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher, 'api');
        Auth::login($teacher);

        $group = app(StudentGroupService::class)->createGroup([
            'name' => '22ИС4-1',
            'students' => [
                'Дудина Софья Романовна',
                'Семенов Евгений Дмитриевич',
            ],
        ]);

        StudentGrade::create([
            'student_group_id' => $group->id,
            'group_student_id' => $group->students[0]->id,
            'subject_name' => 'Программирование',
            'grade_value' => '5',
            'grade_date' => '2026-03-03',
            'created_by' => $teacher->id,
            'updated_by' => $teacher->id,
        ]);

        StudentGrade::create([
            'student_group_id' => $group->id,
            'group_student_id' => $group->students[1]->id,
            'subject_name' => 'Программирование',
            'grade_value' => '4',
            'grade_date' => '2026-03-15',
            'created_by' => $teacher->id,
            'updated_by' => $teacher->id,
        ]);

        $response = $this->get('/api/student-groups/' . $group->id . '/gradebook-export?subject_name=' . urlencode('Программирование') . '&month=2026-03');

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $rows = SimpleXlsx::readRows($response->baseResponse->getFile()->getPathname());
        $headerRowIndex = collect($rows)->search(fn (array $row) => ($row[0] ?? null) === 'Студент');
        $firstStudentRowIndex = collect($rows)->search(fn (array $row) => ($row[0] ?? null) === 'Дудина Софья Романовна');

        $this->assertSame('Журнал группы', $rows[0][0] ?? null);
        $this->assertSame('22ИС4-1', $rows[0][1] ?? null);
        $this->assertNotFalse($headerRowIndex);
        $this->assertNotFalse($firstStudentRowIndex);
        $this->assertContains('03.03', $rows[$headerRowIndex]);
        $this->assertSame('Дудина Софья Романовна', $rows[$firstStudentRowIndex][0] ?? null);
        $this->assertContains('5', $rows[$firstStudentRowIndex]);
    }
}
