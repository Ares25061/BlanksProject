<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectronicTestAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'electronic_test_attempt_id',
        'question_id',
        'answer_id',
        'selected_answers',
        'is_correct',
        'points_earned',
    ];

    protected $casts = [
        'selected_answers' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
    ];

    public function attempt()
    {
        return $this->belongsTo(ElectronicTestAttempt::class, 'electronic_test_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function answer()
    {
        return $this->belongsTo(Answer::class);
    }
}
