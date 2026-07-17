<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('activitylog:clean')->daily();
Schedule::command('tokens:prune')->daily();

// Every 15 minutes, not hourly — tokens can have a lifetime as short as 12 minutes
// (see Key Business Rules), so an hourly cadence could leave the shortest-lived tokens
// unnoticed for up to ~48 extra minutes past expiry. 15 minutes keeps worst-case
// notification lag roughly proportional to the finest-grained lifetime tier, while
// staying cheap (a single indexed expires_at/expiry_notified query over a small table).
Schedule::command('tokens:notify-expired')->everyFifteenMinutes();