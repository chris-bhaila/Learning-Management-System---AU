<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

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
        return $this->belongsTo(ActivityLog::class, 'log_id');
    }
}