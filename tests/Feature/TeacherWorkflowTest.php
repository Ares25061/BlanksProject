<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\BlankForm;
use App\Models\Question;
use App\Models\StudentGrade;
use App\Models\Test;
use App\Models\User;
use App\Services\TestService;
use App\Services\BlankFormService;
use App\Services\GradingService;
use App\Services\StudentGradeService;
use App\Services\StudentGroupService;
use App\Support\BlankScanLayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_test_service_rejects_more_than_fifteen_questions(): void
    {
        $teacher = User::factory()->create();
        $this->actingAs($teacher);
        Auth::login($teacher);

        $questions = collect(range(1, BlankScanLayout::maxQuestions() + 1))
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

        $this->expectException(ValidationException::class);

        app(TestService::class)->createTest([
            'title' => 'Слишком много вопросов',
            'grade_criteria' => [
                ['label' => '5', 'min_points' => 0],
            ],
            'questions' => $questions,
        ]);
    }

    public function test_test_service_rejects_more_than_five_answers_in_question(): void
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
}
