<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'time_limit' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean'
        ];

        // Для создания теста с вопросами
        if ($this->isMethod('post')) {
            $rules['questions'] = 'sometimes|array';
            $rules['questions.*.question_text'] = 'required_with:questions|string';
            $rules['questions.*.type'] = 'required_with:questions|in:single,multiple';
            $rules['questions.*.points'] = 'nullable|integer|min:1';
            $rules['questions.*.answers'] = 'required_with:questions|array|min:1';
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
            'questions.*.answers.*.answer_text.required' => 'Текст ответа обязателен'
        ];
    }
}
