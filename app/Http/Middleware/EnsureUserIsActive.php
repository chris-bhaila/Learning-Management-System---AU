<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Registered globally on the 'web' middleware group (see bootstrap/app.php), not per role
 * group — it must catch a deactivated user before AdminMiddleware/TeacherMiddleware/
 * StudentMiddleware's own is_active check, which only rejects the current request and
 * never logs the user out. Left alone, that produces an infinite redirect loop: the 403
 * from those middlewares gets caught by bootstrap/app.php's exception handler, which
 * redirects back to that same role's dashboard based on role alone (ignoring is_active),
 * which 403s again, forever. This middleware ends the session outright so that never happens.
 *
 * No-ops for guests and for any active authenticated user — this is a no-op read of
 * Auth::user()->is_active on the already-resolved session user, not an extra query.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')
                ->with('error', 'Your account has been deactivated. Contact your administrator.');
        }

        return $next($request);
    }
}
