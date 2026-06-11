<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function notificationReads(): HasMany
    {
        return $this->hasMany(NotificationRead::class, 'log_id');
    }

    protected function icon(): Attribute
    {
        return Attribute::get(fn() => match (true) {
            str_starts_with($this->action, 'user.enrolled')   => 'person_add',
            str_starts_with($this->action, 'course.created')  => 'library_books',
            str_starts_with($this->action, 'course.updated')  => 'edit',
            str_starts_with($this->action, 'course.deleted')  => 'delete',
            str_starts_with($this->action, 'unit.created')    => 'article',
            str_starts_with($this->action, 'unit.updated')    => 'edit_note',
            str_starts_with($this->action, 'unit.deleted')    => 'delete',
            str_starts_with($this->action, 'user.created')    => 'person',
            str_starts_with($this->action, 'user.deleted')    => 'person_remove',
            str_starts_with($this->action, 'user.role')       => 'manage_accounts',
            str_starts_with($this->action, 'token.')          => 'key',
            default                                            => 'info',
        });
    }

    protected function description(): Attribute
    {
        return Attribute::get(function () {
            $actor = $this->user?->name ?? 'Someone';
            $meta  = $this->metadata ?? [];

            return match (true) {
                str_starts_with($this->action, 'user.enrolled_class')  =>
                    "{$actor} joined a teacher's class",
                str_starts_with($this->action, 'user.enrolled_course') =>
                    "{$actor} enrolled in " . ($meta['course_title'] ?? 'a course'),
                str_starts_with($this->action, 'course.created')       =>
                    "{$actor} created course \"" . ($meta['course_title'] ?? 'Untitled') . '"',
                str_starts_with($this->action, 'course.updated')       =>
                    "{$actor} updated course \"" . ($meta['course_title'] ?? 'Untitled') . '"',
                str_starts_with($this->action, 'course.deleted')       =>
                    "{$actor} deleted a course",
                str_starts_with($this->action, 'unit.created')         =>
                    "{$actor} added unit \"" . ($meta['unit_title'] ?? 'Untitled') . '"',
                str_starts_with($this->action, 'unit.updated')         =>
                    "{$actor} updated a unit",
                str_starts_with($this->action, 'user.created')         =>
                    "{$actor} joined EduNest",
                str_starts_with($this->action, 'user.deleted')         =>
                    "A user account was removed",
                str_starts_with($this->action, 'user.role')            =>
                    "{$actor}'s role was updated",
                str_starts_with($this->action, 'token.')               =>
                    "{$actor} used a token",
                default                                                 =>
                    ucfirst(str_replace(['.', '_'], ' ', $this->action)),
            };
        });
    }
}