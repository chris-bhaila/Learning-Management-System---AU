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
            'admin'   => \App\Http\Middleware\AdminMiddleware::class,
            'teacher' => \App\Http\Middleware\TeacherMiddleware::class,
            'student' => \App\Http\Middleware\StudentMiddleware::class,
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
