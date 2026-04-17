<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_group_id',
        'full_name',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function group()
    {
        return $this->belongsTo(StudentGroup::class, 'student_group_id');
    }

    public function blankForms()
    {
        return $this->hasMany(BlankForm::class);
    }

    public function gradebookEntries()
    {
        return $this->hasMany(StudentGrade::class)
            ->orderByDesc('grade_date')
            ->orderByDesc('updated_at');
    }

    public function electronicSessionMembers()
    {
        return $this->hasMany(ElectronicTestSessionMember::class);
    }

    public function electronicAttempts()
    {
        return $this->hasMany(ElectronicTestAttempt::class);
    }
}
