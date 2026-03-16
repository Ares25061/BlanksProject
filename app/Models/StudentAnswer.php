<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    use HasFactory;

    protected $table = 'student_answers';

    protected $fillable = [
        'blank_form_id',
        'question_id',
        'answer_id',
        'selected_answers',
        'is_correct',
        'points_earned'
    ];

    protected $casts = [
        'selected_answers' => 'array',
        'is_correct' => 'boolean',
        'points_earned' => 'integer'
    ];

    public function blankForm()
    {
        return $this->belongsTo(BlankForm::class);
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
