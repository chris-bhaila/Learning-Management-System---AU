<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin'        => \App\Http\Middleware\AdminMiddleware::class,
            'teacher'      => \App\Http\Middleware\TeacherMiddleware::class,
            'student'      => \App\Http\Middleware\StudentMiddleware::class,
            'prevent.back' => \App\Http\Middleware\PreventBackNavigation::class,
        ]);

        // Registered once, globally, on the 'web' group — not duplicated into each of the
        // admin/teacher/student/settings route groups in routes/web.php. Runs before those
        // groups' own 'admin'/'teacher'/'student' middleware (global 'web' middleware always
        // precedes route-specific middleware in Laravel's pipeline), so a deactivated user
        // is logged out and redirected before those middlewares' own is_active check would
        // otherwise abort(403) into a redirect loop. See EnsureUserIsActive for why.
        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (AuthorizationException|HttpException $e, Request $request) {
            $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 403;

            if ($statusCode !== 403 || $request->expectsJson()) return null;

            $user     = $request->user();
            $fallback = match (true) {
                $user?->isAdmin() || $user?->role?->name === 'super_admin' => route('admin.dashboard'),
                $user?->isTeacher()                                         => route('teacher.dashboard'),
                $user?->isStudent()                                         => route('student.dashboard'),
                default                                                     => route('login'),
            };

            return redirect($fallback)->with('error', 'You don\'t have permission to do that.');
        });
    })->create();
