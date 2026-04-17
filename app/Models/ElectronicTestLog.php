<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectronicTestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'electronic_test_attempt_id',
        'event_type',
        'payload',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function attempt()
    {
        return $this->belongsTo(ElectronicTestAttempt::class, 'electronic_test_attempt_id');
    }
}
