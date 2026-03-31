<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'question_text',
        'type',
        'points',
        'order',
        'variant_number',
    ];

    protected $casts = [
        'type' => 'string',
        'points' => 'integer',
        'order' => 'integer',
        'variant_number' => 'integer',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class)->orderBy('order');
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }
}
