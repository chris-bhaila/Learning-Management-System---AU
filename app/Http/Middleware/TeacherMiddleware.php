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

        // headers->set() (not ->header()) works for every response type, including a raw
        // Symfony StreamedResponse (e.g. from response()->streamDownload()), which has no
        // ->header() method and would fatal here otherwise.
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}