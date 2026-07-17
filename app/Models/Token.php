<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Token extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            // expiry_notified and revoked_at are both internal bookkeeping/state changes
            // that already get their own explicit, descriptive activity log entry (see
            // EloquentTokenRepository::logExpiry()/logRevocation()) — excluded here so
            // setting either doesn't ALSO create a generic "updated" audit row alongside
            // the real, descriptive one.
            ->logExcept(['expiry_notified', 'revoked_at'])
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
        'expiry_notified',
        'revoked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'expiry_notified' => 'boolean',
        'revoked_at' => 'datetime',
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

    /** True for ANY reason a token is no longer usable — revoked, naturally time-expired,
     *  or use-limit exhausted. Revoked is checked first and short-circuits the rest:
     *  a revoked token is immediately invalid regardless of its expires_at/uses_count. */
    public function isExpired(): bool
    {
        return $this->isRevoked()
            || $this->expires_at->isPast()
            || $this->uses_count >= $this->max_uses;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function incrementUses(): void
    {
        $this->increment('uses_count');
    }
}