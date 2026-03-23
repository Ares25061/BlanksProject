<?php
// app/Http/Requests/TestRequest.php
namespace App\Http\Requests;

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
            'description' => 'nullable|string',
            'time_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean'
        ];

        // Для создания или обновления теста с вопросами
        if ($this->isMethod('post') || $this->isMethod('put')) {
            $rules['questions'] = 'sometimes|array';
            $rules['questions.*.id'] = 'sometimes|integer|exists:questions,id';
            $rules['questions.*.question_text'] = 'required_with:questions|string';
            $rules['questions.*.type'] = 'required_with:questions|in:single,multiple';
            $rules['questions.*.points'] = 'nullable|integer|min:1';
            $rules['questions.*.answers'] = 'required_with:questions|array|min:2';
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
            'questions.*.question_text.required' => 'Текст вопроса обязателен',
            'questions.*.answers.*.answer_text.required' => 'Текст ответа обязателен',
            'questions.*.answers.min' => 'У вопроса должно быть минимум 2 варианта ответа'
        ];
    }
}
