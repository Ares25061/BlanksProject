<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlankForm extends Model
{
    use HasFactory;

    protected $table = 'blank_forms';

    protected $fillable = [
        'test_id',
        'form_number',
        'last_name',
        'first_name',
        'group_name',
        'submission_date',
        'status',
        'total_score',
        'metadata',
        'checked_by',
        'checked_at'
    ];

    protected $casts = [
        'submission_date' => 'date',
        'checked_at' => 'datetime',
        'metadata' => 'array',
        'total_score' => 'integer'
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function checker()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
