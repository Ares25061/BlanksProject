<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectronicTestAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'electronic_test_session_id',
        'electronic_test_session_member_id',
        'test_id',
        'student_group_id',
        'group_student_id',
        'assigned_grade_by',
        'variant_number',
        'access_token',
        'access_type',
        'student_full_name',
        'is_manual_student',
        'status',
        'total_score',
        'grade_label',
        'assigned_grade_value',
        'assigned_grade_date',
        'metadata',
        'started_at',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'is_manual_student' => 'boolean',
        'variant_number' => 'integer',
        'total_score' => 'integer',
        'assigned_grade_date' => 'date',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(ElectronicTestSession::class, 'electronic_test_session_id');
    }

    public function sessionMember()
    {
        return $this->belongsTo(ElectronicTestSessionMember::class, 'electronic_test_session_member_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function studentGroup()
    {
        return $this->belongsTo(StudentGroup::class);
    }

    public function groupStudent()
    {
        return $this->belongsTo(GroupStudent::class);
    }

    public function gradeAssigner()
    {
        return $this->belongsTo(User::class, 'assigned_grade_by');
    }

    public function answers()
    {
        return $this->hasMany(ElectronicTestAnswer::class, 'electronic_test_attempt_id')
            ->orderBy('question_id');
    }

    public function logs()
    {
        return $this->hasMany(ElectronicTestLog::class, 'electronic_test_attempt_id')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }
}
