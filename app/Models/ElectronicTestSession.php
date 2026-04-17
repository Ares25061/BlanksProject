<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectronicTestSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_id',
        'student_group_id',
        'created_by',
        'access_token',
        'is_active',
        'settings',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    public function studentGroup()
    {
        return $this->belongsTo(StudentGroup::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(ElectronicTestSessionMember::class, 'electronic_test_session_id')
            ->orderBy('id');
    }

    public function attempts()
    {
        return $this->hasMany(ElectronicTestAttempt::class, 'electronic_test_session_id')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');
    }
}
