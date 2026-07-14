<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;

class GoogleController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
    ) {}

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->user();

        // Google's OpenID Connect userinfo response includes email_verified as a boolean.
        // Reject unverified emails so they cannot be used to hijack an existing account.
        $emailVerified = filter_var(
            $googleUser->getRaw()['email_verified'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$emailVerified) {
            return redirect()->route('login')
                ->with('error', 'Google could not verify your email address. Please use a verified Google account and try again.');
        }

        // 1. Look up by Google ID (repeat sign-ins)
        $user = $this->users->findByGoogleId($googleUser->getId());

        if ($user) {
            $updates = ['name' => $googleUser->getName()];
            // Only update avatar from Google if the user hasn't manually uploaded one.
            if ($user->avatar_source !== 'upload') {
                $updates['avatar']        = $googleUser->getAvatar();
                $updates['avatar_source'] = 'google';
            }
            $this->users->update($user, $updates);
        } else {
            // 2. Look up by email — handles admin-created Teacher/Admin accounts
            $user = $this->users->findByEmail($googleUser->getEmail());

            if ($user) {
                // Attach Google ID to the existing account — never touch their role.
                $updates = [
                    'google_id' => $googleUser->getId(),
                    'name'      => $googleUser->getName(),
                ];
                if ($user->avatar_source !== 'upload') {
                    $updates['avatar']        = $googleUser->getAvatar();
                    $updates['avatar_source'] = 'google';
                }
                $this->users->update($user, $updates);
            } else {
                // 3. Brand-new user — create as student, no password set
                $studentRole = $this->roles->findByName('student');

                $user = $this->users->create([
                    'role_id'       => $studentRole->id,
                    'google_id'     => $googleUser->getId(),
                    'name'          => $googleUser->getName(),
                    'email'         => $googleUser->getEmail(),
                    'avatar'        => $googleUser->getAvatar(),
                    'avatar_source' => 'google',
                ]);
            }
        }

        Auth::login($user, true);

        activity()
            ->causedBy($user)
            ->withProperties(['method' => 'google_oauth'])
            ->event('login')
            ->log('Signed in via Google');

        return redirect()->intended(route($user->panelRoleName() . '.dashboard'));
    }
}
