<?php
namespace App\Services;

use App\Models\Test;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TestService
{
    public function createTest(array $data)
    {
        return DB::transaction(function () use ($data) {
            $test = Test::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
                'time_limit' => $data['time_limit'] ?? null,
                'is_active' => $data['is_active'] ?? true
            ]);

            if (isset($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    $this->addQuestionToTest($test, $questionData);
                }
            }

            return $test->load('questions.answers');
        });
    }

    public function updateTest(Test $test, array $data)
    {
        return DB::transaction(function () use ($test, $data) {
            $test->update([
                'title' => $data['title'] ?? $test->title,
                'description' => $data['description'] ?? $test->description,
                'time_limit' => $data['time_limit'] ?? $test->time_limit,
                'is_active' => $data['is_active'] ?? $test->is_active
            ]);

            return $test;
        });
    }

    public function addQuestionToTest(Test $test, array $questionData)
    {
        $question = $test->questions()->create([
            'question_text' => $questionData['question_text'],
            'type' => $questionData['type'],
            'points' => $questionData['points'] ?? 1,
            'order' => $questionData['order'] ?? ($test->questions()->max('order') + 1)
        ]);

        if (isset($questionData['answers'])) {
            foreach ($questionData['answers'] as $answerData) {
                $question->answers()->create([
                    'answer_text' => $answerData['answer_text'],
                    'is_correct' => $answerData['is_correct'] ?? false,
                    'order' => $answerData['order'] ?? 0
                ]);
            }
        }

        return $question->load('answers');
    }

    public function deleteTest(Test $test)
    {
        return $test->delete();
    }
}
