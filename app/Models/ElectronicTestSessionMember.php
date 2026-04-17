<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectronicTestSessionMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'electronic_test_session_id',
        'group_student_id',
        'variant_number',
        'access_token',
    ];

    protected $casts = [
        'variant_number' => 'integer',
    ];

    public function session()
    {
        return $this->belongsTo(ElectronicTestSession::class, 'electronic_test_session_id');
    }

    public function groupStudent()
    {
        return $this->belongsTo(GroupStudent::class);
    }

    public function attempts()
    {
        return $this->hasMany(ElectronicTestAttempt::class, 'electronic_test_session_member_id');
    }
}
