<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
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

    private function admin(string $email): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
            'email'   => $email,
        ]);
    }

    private function superAdmin(string $email): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => 'super_admin'])->id,
            'email'   => $email,
        ]);
    }

    /** @test */
    public function test_existing_admin_can_sign_in_via_google_with_matching_email(): void
    {
        $admin = $this->admin('admin@example.com');
        $this->mockGoogleUser('google-admin-1', 'admin@example.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin->fresh());
        $this->assertSame('google-admin-1', $admin->fresh()->google_id);
    }

    /** @test */
    public function test_existing_super_admin_can_sign_in_via_google_with_matching_email(): void
    {
        $superAdmin = $this->superAdmin('superadmin@example.com');
        $this->mockGoogleUser('google-superadmin-1', 'superadmin@example.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($superAdmin->fresh());
        $this->assertSame('google-superadmin-1', $superAdmin->fresh()->google_id);
    }

    /** @test */
    public function test_teacher_google_login_is_unaffected(): void
    {
        $teacher = User::factory()->teacher()->create(['email' => 'teacher@example.com']);
        $this->mockGoogleUser('google-teacher-1', 'teacher@example.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('teacher.dashboard'));
        $this->assertAuthenticatedAs($teacher->fresh());
    }

    /** @test */
    public function test_student_google_login_is_unaffected(): void
    {
        $student = User::factory()->student()->create(['email' => 'student@example.com']);
        $this->mockGoogleUser('google-student-1', 'student@example.com');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($student->fresh());
    }

    /** @test */
    public function test_new_google_user_is_created_as_student(): void
    {
        Role::firstOrCreate(['name' => 'student']);
        $this->mockGoogleUser('google-new-1', 'brandnew@example.com', 'Brand New');

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseHas('users', [
            'email'     => 'brandnew@example.com',
            'google_id' => 'google-new-1',
        ]);
    }

    /**
     * Confirms the fix in GoogleController::callback() — previously a deactivated
     * user signing in via Google got a "successful" redirect to their dashboard and
     * was only caught and bounced by the global EnsureUserIsActive middleware on
     * their NEXT request. Mirrors AuthController::login()'s existing password-path
     * behavior: reject immediately, in the SAME response, with the same message.
     */
    public function test_deactivated_user_signing_in_via_google_is_logged_out_immediately_not_on_next_request(): void
    {
        $student = User::factory()->student()->create([
            'email'     => 'deactivated@example.com',
            'google_id' => 'google-deactivated-1',
            'is_active' => false,
        ]);
        $this->mockGoogleUser('google-deactivated-1', 'deactivated@example.com', 'Deactivated Student');

        $response = $this->get(route('auth.google.callback'));

        // The whole point of the fix: rejected in THIS response, not a delayed bounce.
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Your account has been deactivated. Contact your administrator.');
        $this->assertGuest();

        // No lingering "signed in" activity log entry for a login that was immediately reversed.
        $this->assertDatabaseMissing('activity_log', [
            'causer_id'   => $student->id,
            'description' => 'Signed in via Google',
        ]);
    }
}
