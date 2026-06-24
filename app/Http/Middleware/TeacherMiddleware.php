<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->isTeacher() || !Auth::user()->is_active) {
            abort(403);
        }

        return $next($request)->header('Cache-Control', 'no-store');
    }
}