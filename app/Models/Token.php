<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Token extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'teacher_id',
        'course_id',
        'token_value',
        'type',
        'expires_at',
        'max_uses',
        'uses_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function isClassToken(): bool
    {
        return $this->type === 'class';
    }

    public function isCourseToken(): bool
    {
        return $this->type === 'course';
    }

    public function formattedValue(): string
    {
        $size = $this->type === 'class' ? 3 : 2;
        return implode('-', str_split($this->token_value, $size));
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->uses_count >= $this->max_uses;
    }

    public function incrementUses(): void
    {
        $this->increment('uses_count');
    }
}