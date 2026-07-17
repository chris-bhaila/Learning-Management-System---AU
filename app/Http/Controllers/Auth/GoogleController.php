<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
            } elseif ($trashedUser = $this->users->findTrashedByEmail($googleUser->getEmail())) {
                // 2b. A previously-deleted account with this email — email/google_id are
                // plain unique constraints (not partial), so a genuinely new row can never
                // be created here; the row must be restored instead of colliding on insert.
                // Deliberately does NOT touch teacher_student/course_student pivot rows —
                // restoring the account must not silently resurrect stale enrollments, the
                // student still needs a fresh token to rejoin a class/course.
                $updates = [
                    'google_id' => $googleUser->getId(),
                    'name'      => $googleUser->getName(),
                ];
                if ($trashedUser->avatar_source !== 'upload') {
                    $updates['avatar']        = $googleUser->getAvatar();
                    $updates['avatar_source'] = 'google';
                }
                $user = $this->users->restore($trashedUser, $updates);

                activity()
                    ->causedBy($user)
                    ->withProperties(['method' => 'google_oauth'])
                    ->log('Restored previously-deleted account on Google sign-in');
            } else {
                // 3. Brand-new user — create as student, no password set.
                // Wrapped in a transaction so the welcome email is only ever queued
                // once the user row is guaranteed committed, via DB::afterCommit() —
                // correct regardless of queue backend, not just incidentally safe
                // because QUEUE_CONNECTION currently happens to share this connection.
                $studentRole = $this->roles->findByName('student');

                try {
                    $user = DB::transaction(function () use ($studentRole, $googleUser) {
                        $newUser = $this->users->create([
                            'role_id'       => $studentRole->id,
                            'google_id'     => $googleUser->getId(),
                            'name'          => $googleUser->getName(),
                            'email'         => $googleUser->getEmail(),
                            'avatar'        => $googleUser->getAvatar(),
                            'avatar_source' => 'google',
                        ]);

                        DB::afterCommit(fn () => Mail::to($newUser)->queue(new WelcomeEmail($newUser)));

                        return $newUser;
                    });
                } catch (QueryException $e) {
                    if (! $this->isEmailUniqueViolation($e)) {
                        throw $e;
                    }

                    // Race loser: another request (e.g. a double-clicked "Sign in with
                    // Google" button) created this exact user between our findByEmail()
                    // check above and this create() call. Fall through to a normal login
                    // for the now-existing account — do NOT queue a second welcome email,
                    // the winning request already queued one.
                    $user = $this->users->findByEmail($googleUser->getEmail());
                }
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

    /** Narrowly matches the users.email unique-constraint violation specifically —
     *  SQLSTATE 23000 alone is too broad (it also covers the users.google_id unique
     *  constraint and any other integrity error), so the index/column name is checked
     *  too. Message format differs by driver: MySQL/MariaDB names the index
     *  ("users_email_unique"), SQLite names the table.column ("users.email"). */
    private function isEmailUniqueViolation(QueryException $e): bool
    {
        if ($e->getCode() !== '23000') {
            return false;
        }

        $message = $e->getMessage();

        return str_contains($message, 'users_email_unique')
            || str_contains($message, 'UNIQUE constraint failed: users.email');
    }
}
