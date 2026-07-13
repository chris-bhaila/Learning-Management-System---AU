<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeactivatedUserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_deactivating_a_logged_in_teacher_mid_session_logs_them_out_on_next_request(): void
    {
        $teacher = User::factory()->teacher()->create(['is_active' => true]);

        // Confirm the active user works normally first.
        $this->actingAs($teacher)->get(route('teacher.dashboard'))->assertOk();

        // Admin deactivates them mid-session.
        $teacher->update(['is_active' => false]);

        // Next request from the SAME (still "logged in" per the session cookie) user.
        $response = $this->get(route('teacher.dashboard'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'Your account has been deactivated. Contact your administrator.');
        $this->assertGuest();

        // No infinite loop: a further request to the same protected route now behaves
        // like any other unauthenticated request (redirect to login), not another loop.
        $followUp = $this->get(route('teacher.dashboard'));
        $followUp->assertRedirect(route('login'));
    }

    /** @test */
    public function test_deactivating_a_logged_in_admin_mid_session_logs_them_out(): void
    {
        $admin = User::factory()->create([
            'role_id'   => Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $admin->update(['is_active' => false]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'Your account has been deactivated. Contact your administrator.');
        $this->assertGuest();
    }

    /** @test */
    public function test_deactivating_a_logged_in_student_mid_session_logs_them_out(): void
    {
        $student = User::factory()->student()->create(['is_active' => true]);

        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();

        $student->update(['is_active' => false]);

        $response = $this->get(route('student.dashboard'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'Your account has been deactivated. Contact your administrator.');
        $this->assertGuest();
    }

    /** @test */
    public function test_active_user_is_entirely_unaffected(): void
    {
        $teacher = User::factory()->teacher()->create(['is_active' => true]);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk();
        $this->assertAuthenticatedAs($teacher);
    }

    /** @test */
    public function test_deactivated_user_cannot_log_in_and_gets_a_clear_message_at_the_login_form(): void
    {
        $teacher = User::factory()->teacher()->create(['is_active' => false]);

        $response = $this->post(route('auth.login'), [
            'email'    => $teacher->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Your account has been deactivated. Contact your administrator.',
        ]);
        $this->assertGuest();
    }
}
