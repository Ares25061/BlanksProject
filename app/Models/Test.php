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
        'test_status',
        'grade_criteria',
        'variant_count',
        'delivery_mode',
        'access_code',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'test_status' => 'string',
        'time_limit' => 'integer',
        'grade_criteria' => 'array',
        'variant_count' => 'integer',
        'delivery_mode' => 'string',
    ];

    protected $appends = [
        'test_status_label',
        'is_closed',
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

    public function electronicSessions()
    {
        return $this->hasMany(ElectronicTestSession::class)
            ->orderByDesc('created_at');
    }

    public function electronicAttempts()
    {
        return $this->hasMany(ElectronicTestAttempt::class)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');
    }

    public function getSubjectDisplayNameAttribute(): string
    {
        return trim((string) ($this->subject_name ?: $this->title));
    }

    public function getMaxScoreAttribute(): int
    {
        return (int) $this->questions->sum('points');
    }

    public function getDeliveryModeLabelAttribute(): string
    {
        return match ((string) $this->delivery_mode) {
            'electronic' => 'Электронный',
            'hybrid' => 'Совмещённый',
            default => 'На бланках',
        };
    }

    public function getTestStatusLabelAttribute(): string
    {
        return match ((string) $this->test_status) {
            'closed' => 'Закрыт',
            'draft' => 'Черновик',
            default => 'Активен',
        };
    }

    public function getIsClosedAttribute(): bool
    {
        return (string) $this->test_status === 'closed';
    }
}
