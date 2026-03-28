<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_group_id',
        'group_student_id',
        'blank_form_id',
        'subject_name',
        'grade_value',
        'grade_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'grade_date' => 'date',
    ];

    public function studentGroup()
    {
        return $this->belongsTo(StudentGroup::class);
    }

    public function groupStudent()
    {
        return $this->belongsTo(GroupStudent::class);
    }

    public function blankForm()
    {
        return $this->belongsTo(BlankForm::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
