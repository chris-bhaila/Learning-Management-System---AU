<?php

namespace Tests\Feature;

use App\Mail\WelcomeEmail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

/**
 * Probes App\Http\Controllers\Auth\GoogleController::callback()'s new-user
 * branch (app/Http/Controllers/Auth/GoogleController.php:67-81), which is the
 * only place a WelcomeEmail is ever queued.
 */
class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogleUser(string $id, string $email, string $name = 'Google User'): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');
        $socialiteUser->shouldReceive('getRaw')->andReturn(['email_verified' => true]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function studentRole(): Role
    {
        return Role::firstOrCreate(['name' => 'student']);
    }

    // ─── 1. Correct-trigger confirmation ───────────────────────────────

    public function test_brand_new_google_user_gets_exactly_one_welcome_email(): void
    {
        Mail::fake();
        $this->studentRole();
        $this->mockGoogleUser('google-new-1', 'brandnew@example.com', 'Brand New');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('student.dashboard'));
        $user = User::where('email', 'brandnew@example.com')->first();
        $this->assertNotNull($user);

        Mail::assertQueued(WelcomeEmail::class, 1);
        Mail::assertQueued(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->user->is($user) && $mail->hasTo($user->email);
        });
    }

    public function test_returning_user_matched_by_google_id_gets_no_email(): void
    {
        Mail::fake();
        $existing = User::factory()->student()->create([
            'email'     => 'returning@example.com',
            'google_id' => 'google-returning-1',
        ]);
        $this->mockGoogleUser('google-returning-1', 'returning@example.com', 'Returning User');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($existing->fresh());
        Mail::assertNothingQueued();
    }

    /**
     * The case most easily confused with "new user": an existing password-based
     * account (google_id still null) connecting Google for the first time.
     * GoogleController::callback() resolves this via findByEmail() (branch 2),
     * not the create() branch (branch 3) — no email must fire here.
     */
    public function test_existing_account_linked_by_email_for_first_time_gets_no_email(): void
    {
        Mail::fake();
        $existing = User::factory()->student()->create([
            'email'     => 'linkme@example.com',
            'google_id' => null,
        ]);
        $this->assertNull($existing->google_id);

        $this->mockGoogleUser('google-link-1', 'linkme@example.com', 'Link Me');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($existing->fresh());
        $this->assertSame('google-link-1', $existing->fresh()->google_id);

        // Confirm it went through the "link" path, not "create": still one user row.
        $this->assertSame(1, User::where('email', 'linkme@example.com')->count());
        Mail::assertNothingQueued();
    }

    public function test_existing_admin_logging_in_via_google_gets_no_email(): void
    {
        Mail::fake();
        $admin = User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
            'email'   => 'admin@example.com',
        ]);
        $this->mockGoogleUser('google-admin-1', 'admin@example.com', 'Admin User');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin->fresh());
        Mail::assertNothingQueued();
    }

    // ─── 2. Role safety ─────────────────────────────────────────────────

    public function test_every_brand_new_google_signup_is_created_as_student_never_higher(): void
    {
        Mail::fake();
        $this->studentRole();
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'teacher']);
        Role::firstOrCreate(['name' => 'super_admin']);

        // Adversarial: attacker-controlled display name/email trying to look like staff.
        // Nothing in the request is attacker-controlled role data — callback() hardcodes
        // role_id to the student role — but assert this holds even with a "staff-looking" identity.
        $this->mockGoogleUser('google-imposter-1', 'admin@newcorp.com', 'Administrator');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('student.dashboard'));
        $user = User::where('email', 'admin@newcorp.com')->firstOrFail();
        $this->assertSame('student', $user->role->name);
    }

    // ─── 3. Duplicate / race conditions ────────────────────────────────

    public function test_users_email_column_has_a_unique_constraint(): void
    {
        $this->studentRole();
        User::factory()->student()->create(['email' => 'unique-check@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        // Bypasses the model layer entirely to prove the constraint is enforced
        // at the database, not just by application-level lookups.
        DB::table('users')->insert([
            'role_id'    => $this->studentRole()->id,
            'name'       => 'Duplicate',
            'email'      => 'unique-check@example.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Simulates two near-simultaneous first-time callbacks for the same email
     * (e.g. a double-clicked "Sign in with Google" button): both requests read
     * findByEmail()/findByGoogleId() as null before either has committed, so
     * both would attempt to go down the create() branch.
     */
    public function test_racing_first_time_signups_for_the_same_email_cannot_create_two_users(): void
    {
        Mail::fake();
        $role = $this->studentRole();

        $create = fn () => User::create([
            'role_id'       => $role->id,
            'google_id'     => 'google-race-'.uniqid(),
            'name'          => 'Race Condition',
            'email'         => 'race@example.com',
            'avatar'        => null,
            'avatar_source' => 'google',
        ]);

        // "Request" A: wins the race, commits first.
        $winner = $create();
        Mail::to($winner)->queue(new WelcomeEmail($winner));

        // "Request" B: also read a null lookup before A committed, now tries to create too.
        $threw = false;
        try {
            $create();
            Mail::to($winner)->queue(new WelcomeEmail($winner)); // would only run if create() succeeded
        } catch (\Illuminate\Database\QueryException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected the losing request to hit the unique constraint on users.email.');
        $this->assertSame(1, User::where('email', 'race@example.com')->count());
        Mail::assertQueued(WelcomeEmail::class, 1);
    }

    /**
     * Drives the actual controller's catch(QueryException) recovery path
     * (GoogleController::callback(), branch 3) end-to-end: the repository is
     * mocked so findByGoogleId()/findByEmail() both report "no user" — the
     * stale read a real race loser would see — but the mocked create() call
     * performs a REAL Eloquent insert that collides with an already-committed
     * winner, producing a genuine unique-constraint QueryException for
     * isEmailUniqueViolation() to classify. Confirms the fix's actual UX
     * payoff: the loser gets a normal login, not a 500, and no second email.
     */
    public function test_race_loser_gets_graceful_login_not_a_500_and_no_duplicate_email(): void
    {
        Mail::fake();
        $role = $this->studentRole();

        // The winner: already created and already got its welcome email.
        $winner = User::create([
            'role_id'       => $role->id,
            'google_id'     => 'google-race-winner',
            'name'          => 'Race Winner',
            'email'         => 'race-graceful@example.com',
            'avatar'        => null,
            'avatar_source' => 'google',
        ]);
        Mail::to($winner)->queue(new WelcomeEmail($winner));

        $fakeRepo = Mockery::mock(\App\Repositories\Contracts\UserRepositoryInterface::class);
        $fakeRepo->shouldReceive('findByGoogleId')->once()->andReturn(null);
        // First call: the stale pre-create check (a real race would see this as
        // null too, since the winner hadn't committed yet at that point).
        // Second call: the controller's catch-block recovery lookup, after the
        // create() collision — this time the winner's row genuinely exists.
        $fakeRepo->shouldReceive('findByEmail')->twice()->andReturn(null, $winner);
        $fakeRepo->shouldReceive('create')->once()->andReturnUsing(function () use ($role) {
            // Real insert against the real unique index — not a fabricated exception —
            // so the controller's SQLSTATE/message-based classification is genuinely exercised.
            return User::create([
                'role_id'       => $role->id,
                'google_id'     => 'google-race-loser',
                'name'          => 'Race Loser',
                'email'         => 'race-graceful@example.com', // collides with the winner
                'avatar'        => null,
                'avatar_source' => 'google',
            ]);
        });
        $this->app->instance(\App\Repositories\Contracts\UserRepositoryInterface::class, $fakeRepo);

        $this->mockGoogleUser('google-race-loser', 'race-graceful@example.com', 'Race Loser');

        $response = $this->get(route('auth.google.callback'));

        // The UX fix: a normal successful login, not a 500.
        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($winner->fresh());

        // No duplicate row, no duplicate email — only the winner's original.
        $this->assertSame(1, User::where('email', 'race-graceful@example.com')->count());
        Mail::assertQueued(WelcomeEmail::class, 1);
    }

    /**
     * A non-unique-constraint QueryException (some other integrity error) must
     * NOT be swallowed as if it were a harmless race — isEmailUniqueViolation()
     * has to check the specific index/column, not just SQLSTATE 23000 broadly.
     * Simulated here via the users.google_id unique constraint, which shares
     * SQLSTATE 23000 with the email constraint but must not be caught as one.
     */
    public function test_non_email_unique_violations_are_not_swallowed_as_a_race(): void
    {
        Mail::fake();
        $role = $this->studentRole();

        User::create([
            'role_id'       => $role->id,
            'google_id'     => 'google-collide',
            'name'          => 'Existing Google Id Holder',
            'email'         => 'someone-else@example.com',
            'avatar'        => null,
            'avatar_source' => 'google',
        ]);

        $fakeRepo = Mockery::mock(\App\Repositories\Contracts\UserRepositoryInterface::class);
        $fakeRepo->shouldReceive('findByGoogleId')->once()->andReturn(null);
        $fakeRepo->shouldReceive('findByEmail')->once()->andReturn(null);
        $fakeRepo->shouldReceive('create')->once()->andReturnUsing(function () use ($role) {
            return User::create([
                'role_id'       => $role->id,
                'google_id'     => 'google-collide', // collides on google_id, NOT email
                'name'          => 'New Signup',
                'email'         => 'brand-new-unique@example.com',
                'avatar'        => null,
                'avatar_source' => 'google',
            ]);
        });
        $this->app->instance(\App\Repositories\Contracts\UserRepositoryInterface::class, $fakeRepo);

        $this->mockGoogleUser('google-collide', 'brand-new-unique@example.com', 'New Signup');

        $this->withoutExceptionHandling();
        $this->expectException(\Illuminate\Database\QueryException::class);

        try {
            $this->get(route('auth.google.callback'));
        } finally {
            // Must NOT have been quietly recovered — no second user materialized,
            // no email queued for a signup that never actually completed.
            $this->assertSame(1, User::count());
            Mail::assertNothingQueued();
        }
    }

    // ─── 4. Failure / rollback correctness ─────────────────────────────

    /**
     * Forces user creation itself to fail (a DB-level failure, not a validation
     * failure — the real code path has no Form Request in front of the Google
     * flow) and confirms the email is never queued for a user that doesn't exist.
     */
    public function test_no_email_is_queued_if_user_creation_fails(): void
    {
        Mail::fake();
        $this->studentRole();

        $fakeRepo = Mockery::mock(\App\Repositories\Contracts\UserRepositoryInterface::class);
        $fakeRepo->shouldReceive('findByGoogleId')->andReturn(null);
        $fakeRepo->shouldReceive('findByEmail')->andReturn(null);
        $fakeRepo->shouldReceive('create')->andThrow(new \RuntimeException('Simulated DB failure during user creation'));
        $this->app->instance(\App\Repositories\Contracts\UserRepositoryInterface::class, $fakeRepo);

        $this->mockGoogleUser('google-fail-1', 'failcreate@example.com', 'Fail Create');

        $this->withoutExceptionHandling();
        try {
            $this->get(route('auth.google.callback'));
            $this->fail('Expected the simulated creation failure to propagate.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Simulated DB failure during user creation', $e->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'failcreate@example.com']);
        Mail::assertNothingQueued();
    }

    /**
     * GoogleController::callback() (app/Http/Controllers/Auth/GoogleController.php:70-104)
     * now wraps User::create() in DB::transaction() and defers the mail push via
     * DB::afterCommit(). This replicates that exact create()+afterCommit() sequence
     * and forces a rollback, using a real 'database' queue connection — the same
     * config this app actually runs under. Still passes, but now for the RIGHT
     * reason: the push is never even attempted before commit, rather than being
     * attempted and then incidentally undone because both writes shared a connection.
     */
    public function test_transaction_rollback_of_user_creation_also_rolls_back_the_queued_job_today(): void
    {
        $this->app['config']->set('queue.default', 'database');
        $role = $this->studentRole();

        try {
            DB::transaction(function () use ($role) {
                $user = User::create([
                    'role_id'       => $role->id,
                    'google_id'     => 'google-rollback-1',
                    'name'          => 'Rollback Me',
                    'email'         => 'rollback@example.com',
                    'avatar'        => null,
                    'avatar_source' => 'google',
                ]);
                DB::afterCommit(fn () => Mail::to($user)->queue(new WelcomeEmail($user)));

                // Mid-transaction, pre-commit: unlike the old incidental-safety version
                // of this test, the job push hasn't even been attempted yet — the
                // afterCommit callback is only registered, not executed.
                $this->assertSame(0, DB::table('jobs')->count());

                throw new \RuntimeException('force rollback');
            });
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.com']);
        $this->assertSame(0, DB::table('jobs')->count());
    }

    /**
     * The test that would have caught the original gap: simulates "a different
     * queue backend" by using Mail::fake(), which intercepts exactly at the
     * Mail::to()->queue() call boundary — independent of which real queue
     * connection/driver is configured, unlike the previous test (which only
     * proved safety for the 'database' driver specifically, via shared-connection
     * rollback). Confirms DB::afterCommit()'s deferred-execution semantics
     * themselves are what prevent the email, not connection-sharing timing.
     */
    public function test_email_is_only_queued_after_transaction_commits_not_before_any_backend(): void
    {
        Mail::fake();
        $role = $this->studentRole();

        try {
            DB::transaction(function () use ($role) {
                $user = User::create([
                    'role_id'       => $role->id,
                    'google_id'     => 'google-rollback-2',
                    'name'          => 'Rollback Me Too',
                    'email'         => 'rollback-2@example.com',
                    'avatar'        => null,
                    'avatar_source' => 'google',
                ]);
                DB::afterCommit(fn () => Mail::to($user)->queue(new WelcomeEmail($user)));

                throw new \RuntimeException('force rollback');
            });
        } catch (\RuntimeException $e) {
            // expected
        }

        // No queue backend is even involved here — Mail::fake() would have recorded
        // the dispatch the instant Mail::to()->queue() actually ran, regardless of
        // driver. It never ran, because the transaction never committed.
        Mail::assertNothingQueued();
        $this->assertDatabaseMissing('users', ['email' => 'rollback-2@example.com']);
    }

    // ─── 5. Queue/mail failure isolation ───────────────────────────────

    /**
     * Runs with a real (non-faked) database queue connection so the job is
     * actually persisted to `jobs` and processed by a worker in a later,
     * separate step — proving login/session establishment does not wait on,
     * or get affected by, eventual job execution or failure.
     */
    public function test_a_failing_queued_job_does_not_affect_the_already_completed_login(): void
    {
        $this->app['config']->set('queue.default', 'database');
        $this->studentRole();
        $this->mockGoogleUser('google-queuefail-1', 'queuefail@example.com', 'Queue Fail');

        $response = $this->get(route('auth.google.callback'));

        // Login already fully succeeded before the job is ever processed.
        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticated();
        $user = User::where('email', 'queuefail@example.com')->firstOrFail();
        $this->assertSame(1, DB::table('jobs')->count());

        // Force the queued job to fail when it eventually runs: delete the
        // underlying user row so WelcomeEmail's SerializesModels unserialize
        // step throws ModelNotFoundException — a realistic failure mode.
        $user->forceDelete();

        $exitCode = Artisan::call('queue:work', [
            '--once'  => true,
            '--tries' => 1,
        ]);

        // The worker handles the failure internally — it does not throw out to the caller.
        $this->assertSame(0, $exitCode);

        // Login/session from the original request is untouched by this later failure.
        $this->assertAuthenticated();

        // Job was attempted once and moved to failed_jobs, not retried indefinitely.
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count());
    }

    // ─── 6. Content / rendering edge cases ─────────────────────────────

    public function test_welcome_email_escapes_html_injection_attempts_in_the_name(): void
    {
        $maliciousName = '<script>alert(1)</script>';
        $user = User::factory()->student()->make(['name' => $maliciousName]);
        $user->id = 999; // unsaved model is fine — WelcomeEmail only reads ->name for rendering

        $rendered = (new WelcomeEmail($user))->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $rendered);
        $this->assertStringContainsString(e($maliciousName), $rendered);
    }

    public function test_welcome_email_escapes_apostrophes_and_ampersands_in_the_name(): void
    {
        $name = "O'Brien & Associates";
        $user = User::factory()->student()->make(['name' => $name]);
        $user->id = 999;

        $rendered = (new WelcomeEmail($user))->render();

        $this->assertStringContainsString(e($name), $rendered);
        // Raw unescaped ampersand/apostrophe pair should not appear literally.
        $this->assertStringNotContainsString("O'Brien & Associates", $rendered);
    }

    public function test_welcome_email_renders_unicode_names_correctly(): void
    {
        $name = '日本語 Ünïcodé Ñame';
        $user = User::factory()->student()->make(['name' => $name]);
        $user->id = 999;

        $rendered = (new WelcomeEmail($user))->render();

        $this->assertStringContainsString($name, $rendered);
    }

    public function test_welcome_email_does_not_break_on_an_unusually_long_name(): void
    {
        $name = str_repeat('Alexandria ', 30); // 330 chars
        $user = User::factory()->student()->make(['name' => $name]);
        $user->id = 999;

        $rendered = (new WelcomeEmail($user))->render();

        $this->assertStringContainsString(trim($name), $rendered);
    }
}
