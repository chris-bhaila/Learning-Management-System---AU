<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        // Explicit logOnly(), not logFillable() — role_id/is_active are deliberately
        // NOT in $fillable (see below; they're privilege-sensitive, set only via
        // explicit attribute assignment), but changes to them are still real audit
        // events that must keep appearing in the automatic created/updated/deleted
        // log exactly as before. logFillable() would silently stop tracking them the
        // moment they left $fillable.
        return LogOptions::defaults()
            ->logOnly([
                'role_id',
                'google_id',
                'name',
                'email',
                'password',
                'avatar',
                'avatar_path',
                'avatar_source',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    // role_id and is_active are deliberately excluded from $fillable — both are
    // privilege-sensitive (role escalation / account activation) and must only ever
    // be set via explicit attribute assignment ($user->role_id = ...; $user->save()),
    // never via mass assignment (User::create($array) / $user->update($array) /
    // $user->fill($array)). EloquentUserRepository::create()/update()/updateRole()
    // are the one place this is done for application code; UserFactory bypasses this
    // deliberately via Model::unguarded() since test/seed data has no untrusted input.
    protected $fillable = [
        'google_id',
        'name',
        'email',
        'password',
        'avatar',
        'avatar_path',
        'avatar_source',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function courseGroups(): HasMany
    {
        return $this->hasMany(CourseGroup::class, 'teacher_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class, 'teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_student', 'teacher_id', 'student_id')
            ->withPivot(['is_active', 'enrolled_at'])
            ->withTimestamps();
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'teacher_student', 'student_id', 'teacher_id')
            ->withPivot(['is_active', 'enrolled_at'])
            ->withTimestamps();
    }

    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_student', 'student_id', 'course_id')
            ->withPivot(['is_active', 'enrolled_at'])
            ->withTimestamps();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notificationReads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }

    public function avatarUrl(): ?string
    {
        return match($this->avatar_source) {
            'upload' => $this->avatar_path
                            ? Storage::disk('public')->url($this->avatar_path)
                            : null,
            'google' => $this->avatar ?: null,
            default  => null,
        };
    }

    public function hasManualAvatar(): bool
    {
        return $this->avatar_source === 'upload';
    }

    /** Admin-or-above — true for both 'admin' and 'super_admin'. This is the gate used
     *  everywhere the admin panel/routes/policies check access; super_admin reuses the
     *  entire admin experience, so it must satisfy every isAdmin() check too. */
    public function isAdmin(): bool
    {
        return in_array($this->role?->name, ['admin', 'super_admin'], true);
    }

    /** Strict — true only for 'super_admin'. Use this (not isAdmin()) for the one
     *  capability exclusive to super_admin: granting the admin role to another user. */
    public function isSuperAdmin(): bool
    {
        return $this->role?->name === 'super_admin';
    }

    /** Route/layout namespace for this user's role — super_admin reuses the entire
     *  admin panel (routes, layout, views), so it resolves to 'admin' here too. */
    public function panelRoleName(): string
    {
        return $this->isAdmin() ? 'admin' : (string) $this->role?->name;
    }

    public function isTeacher(): bool
    {
        return $this->role?->name === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role?->name === 'student';
    }
}