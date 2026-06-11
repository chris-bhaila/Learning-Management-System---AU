<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Course extends Model
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
        'group_id',
        'title',
        'description',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class, 'group_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('order');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_student', 'course_id', 'student_id')
            ->withPivot(['is_active', 'enrolled_at'])
            ->withTimestamps();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }
}