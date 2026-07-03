<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // "Remember me" cookies (password and Google SSO logins) expire after 10 days,
        // instead of Laravel's default (~400 days).
        Auth::setRememberDuration(10 * 24 * 60);
    }
}
