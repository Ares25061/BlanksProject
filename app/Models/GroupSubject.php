<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_group_id',
        'subject_name',
        'created_by',
    ];

    public function group()
    {
        return $this->belongsTo(StudentGroup::class, 'student_group_id');
    }
}
