<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

class NotificationRead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'log_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function log(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'log_id');
    }
}