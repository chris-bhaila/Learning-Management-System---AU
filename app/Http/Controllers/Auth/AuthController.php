<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function showLogin()
    {
        return view('sign-in');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Auth::attempt() only checks credentials — it has no notion of is_active. Without
            // this check, a deactivated user would successfully log in here, then get bounced
            // by EnsureUserIsActive on their very next request. Checking here instead gives a
            // clear message at the login form itself, not a confusing redirect straight back out.
            if (! Auth::user()->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['email' => 'Your account has been deactivated. Contact your administrator.'])
                    ->withInput($request->only('email'));
            }

            $request->session()->regenerate();

            activity()
                ->causedBy(Auth::user())
                ->withProperties(['method' => 'password'])
                ->event('login')
                ->log('Signed in');

            return redirect()->route(Auth::user()->panelRoleName() . '.dashboard');
        }

        // Give a specific message for Google-only accounts (null password)
        $existing = $this->users->findByEmail($credentials['email']);
        if ($existing && is_null($existing->password)) {
            return back()
                ->withErrors(['email' => 'This account uses Google Sign-In. Sign in with Google below — you can set a password from your account settings afterwards.'])
                ->withInput($request->only('email'));
        }

        return back()
            ->withErrors(['email' => 'Invalid credentials.'])
            ->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        activity()
            ->causedBy($user)
            ->event('logout')
            ->log('Signed out');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
