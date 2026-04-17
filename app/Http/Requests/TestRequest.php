<?php
// app/Http/Requests/TestRequest.php
namespace App\Http\Requests;

use App\Support\BlankScanLayout;
use Illuminate\Foundation\Http\FormRequest;

class TestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'subject_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'time_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'test_status' => 'nullable|in:active,draft,closed',
            'variant_count' => 'required|integer|min:1|max:10',
            'delivery_mode' => 'nullable|in:blank,electronic,hybrid',
            'grade_criteria' => 'required|array|min:1',
            'grade_criteria.*.label' => 'required|string|max:255',
            'grade_criteria.*.min_points' => 'required|integer|min:0',
        ];

        // Для создания или обновления теста с вопросами
        if ($this->isMethod('post') || $this->isMethod('put')) {
            $rules['questions'] = 'sometimes|array';
            $rules['questions.*.id'] = 'sometimes|integer|exists:questions,id';
            $rules['questions.*.question_text'] = 'required_with:questions|string';
            $rules['questions.*.type'] = 'required_with:questions|in:single,multiple';
            $rules['questions.*.points'] = 'nullable|integer|min:1';
            $rules['questions.*.variant_number'] = 'nullable|integer|min:1|max:10';
            $rules['questions.*.answers'] = 'required_with:questions|array|min:2|max:' . BlankScanLayout::ANSWER_OPTION_COUNT;
            $rules['questions.*.answers.*.id'] = 'sometimes|integer|exists:answers,id';
            $rules['questions.*.answers.*.answer_text'] = 'required_with:questions.*.answers|string';
            $rules['questions.*.answers.*.is_correct'] = 'boolean';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'title.required' => 'Название теста обязательно',
            'subject_name.required' => 'Укажите предмет теста',
            'variant_count.required' => 'Укажите количество вариантов теста',
            'variant_count.integer' => 'Количество вариантов должно быть числом',
            'variant_count.min' => 'Количество вариантов должно быть не меньше 1',
            'variant_count.max' => 'Поддерживается не более 10 вариантов теста',
            'test_status.in' => 'Статус теста должен быть: активен, черновик или закрыт.',
            'delivery_mode.in' => 'Формат проведения должен быть: бланки, электронно или совмещённо.',
            'questions.*.question_text.required' => 'Текст вопроса обязателен',
            'questions.*.answers.*.answer_text.required' => 'Текст ответа обязателен',
            'questions.*.answers.min' => 'У вопроса должно быть минимум 2 варианта ответа',
            'questions.*.answers.max' => 'Для одного вопроса поддерживается не более ' . BlankScanLayout::ANSWER_OPTION_COUNT . ' вариантов ответа',
            'grade_criteria.required' => 'Укажите критерии оценивания по баллам',
            'grade_criteria.*.label.required' => 'У каждой оценки должна быть подпись',
            'grade_criteria.*.min_points.required' => 'Укажите минимальный балл для каждой оценки',
        ];
    }
}
