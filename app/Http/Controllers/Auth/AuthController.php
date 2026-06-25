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
            $request->session()->regenerate();
            return redirect()->route(Auth::user()->role->name . '.dashboard');
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
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
