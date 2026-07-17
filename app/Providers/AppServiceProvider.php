<?php

namespace App\Providers;

use App\Repositories\Contracts\SiteContentRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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

        // Shares the admin-editable site name/short badge label with every view app-wide
        // (wired once here, not per controller) — see
        // App\Repositories\Eloquent\EloquentSiteContentRepository for the DB-backed,
        // cached source of truth (site.name / site.short_label keys). Scoped to just the
        // base layout names doesn't reach here: Blade's @extends renders a child view's
        // own compiled template (which now also references $siteName directly, e.g.
        // admin/dashboard.blade.php) using only the data that child view was originally
        // created with — a parent-only composer's data is merged in separately, later,
        // when @extends composes the parent. A wildcard composer fires for every view
        // individually (child AND parent), so it reaches both. Resolved lazily here
        // (only when a view is actually rendered, at request time) rather than at boot,
        // so this never runs before migrations exist during tests.
        // No extra in-process memoization here — EloquentSiteContentRepository already
        // caches all() via Cache::rememberForever() and invalidates it on save(), so an
        // additional static cache in this closure would go stale immediately after an
        // admin edit within the same process (e.g. in tests, or a long-running worker),
        // silently reintroducing the exact staleness bug the repository's own cache
        // invalidation is there to prevent.
        View::composer('*', function ($view) {
            $content = app(SiteContentRepositoryInterface::class);
            $view->with('siteName', $content->get('site.name', 'EduNest'));
            $view->with('siteShortLabel', $content->get('site.short_label', 'EN'));
        });
    }
}
