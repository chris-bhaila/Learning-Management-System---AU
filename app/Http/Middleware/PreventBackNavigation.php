<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackNavigation
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Use the Symfony HeaderBag directly (not Laravel's ->header() sugar method) so this
        // works for every response type in these route groups, not just Illuminate\Http\Response
        // — a raw Symfony StreamedResponse (e.g. from response()->streamDownload()) has no
        // ->header() method and would fatal here otherwise.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
