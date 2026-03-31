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
        'student_group_id',
        'group_student_id',
        'form_number',
        'variant_number',
        'last_name',
        'first_name',
        'patronymic',
        'group_name',
        'submission_date',
        'status',
        'total_score',
        'grade_label',
        'assigned_grade_value',
        'assigned_grade_date',
        'assigned_grade_by',
        'scan_path',
        'scanned_at',
        'metadata',
        'checked_by',
        'checked_at'
    ];

    protected $casts = [
        'submission_date' => 'date',
        'assigned_grade_date' => 'date',
        'checked_at' => 'datetime',
        'scanned_at' => 'datetime',
        'metadata' => 'array',
        'total_score' => 'integer',
        'variant_number' => 'integer',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function studentGroup()
    {
        return $this->belongsTo(StudentGroup::class, 'student_group_id');
    }

    public function groupStudent()
    {
        return $this->belongsTo(GroupStudent::class, 'group_student_id');
    }

    public function checker()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function gradeAssigner()
    {
        return $this->belongsTo(User::class, 'assigned_grade_by');
    }

    public function getStudentFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->last_name,
            $this->first_name,
            $this->patronymic,
        ])));
    }
}
