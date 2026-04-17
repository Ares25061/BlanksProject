<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'subject_name',
        'description',
        'created_by',
        'time_limit',
        'is_active',
        'grade_criteria',
        'variant_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'time_limit' => 'integer',
        'grade_criteria' => 'array',
        'variant_count' => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class)
            ->orderBy('order')
            ->orderBy('id');
    }

    public function blankForms()
    {
        return $this->hasMany(BlankForm::class);
    }

    public function getSubjectDisplayNameAttribute(): string
    {
        return trim((string) ($this->subject_name ?: $this->title));
    }

    public function getMaxScoreAttribute(): int
    {
        return (int) $this->questions->sum('points');
    }
}
