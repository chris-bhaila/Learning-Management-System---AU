<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Change Password on Settings must be hidden (view) AND rejected (route) for
 * ANY user with a linked google_id, regardless of role or password history —
 * see App\Http\Requests\UpdatePasswordRequest::authorize() and
 * resources/views/settings/index.blade.php's $isGoogleLinked branch.
 */
class GoogleLinkedPasswordHiddenTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => 'student'])->id,
            'is_active' => true,
        ], $attributes));
    }

    private function admin(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ], $attributes));
    }

    private function superAdmin(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => 'super_admin'])->id,
            'is_active' => true,
        ], $attributes));
    }

    private function passwordPayload(?string $current = null): array
    {
        return [
            'current_password'      => $current,
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];
    }

    // ─── Case 1: password-only user (no google_id) ─────────────────────

    public function test_password_only_user_sees_the_change_password_form_and_can_change_it(): void
    {
        $user = $this->makeUser(['google_id' => null, 'password' => Hash::make('oldpassword')]);

        $response = $this->actingAs($user)->get(route('settings.index'));
        $response->assertOk();
        $response->assertSee('Change Password');
        $response->assertSee('id="settings-current-password"', false);
        $response->assertDontSee("there's no password to manage here", false);

        $update = $this->actingAs($user)
            ->patch(route('settings.password.update'), $this->passwordPayload('oldpassword'));

        $update->assertRedirect();
        $update->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    // ─── Case 2: Google-linked, never had a password ───────────────────

    public function test_google_only_user_never_had_a_password_field_hidden_and_route_rejects(): void
    {
        $user = $this->makeUser(['google_id' => 'google-123', 'password' => null]);

        $response = $this->actingAs($user)->get(route('settings.index'));
        $response->assertOk();
        $response->assertDontSee('Change Password', false);
        $response->assertDontSee('Set Password', false);
        $response->assertDontSee('id="settings-current-password"', false);
        $response->assertDontSee('id="settings-password"', false);
        $response->assertSee("there's no password to manage here", false);

        $update = $this->actingAs($user)
            ->patch(route('settings.password.update'), $this->passwordPayload());

        $update->assertRedirect();
        $update->assertSessionHas('error');
        $this->assertNull($user->fresh()->password);
    }

    // ─── Case 3: Google-linked but ALSO has a real password (e.g. admin who ──
    // later links Google) — the one that could easily be implemented backwards.

    public function test_google_linked_user_with_an_existing_password_still_has_it_hidden(): void
    {
        $admin = $this->admin([
            'google_id' => 'google-admin-456',
            'password'  => Hash::make('originalpassword'),
        ]);

        $response = $this->actingAs($admin)->get(route('settings.index'));
        $response->assertOk();
        // Must be hidden regardless of $hasPassword being true — this is the
        // requirement most likely to get implemented backwards.
        $response->assertDontSee('Change Password', false);
        $response->assertDontSee('••••••••••••', false);
        $response->assertDontSee('id="settings-current-password"', false);
        $response->assertDontSee('id="settings-password"', false);
        $response->assertSee("there's no password to manage here", false);

        $update = $this->actingAs($admin)
            ->patch(route('settings.password.update'), $this->passwordPayload('originalpassword'));

        $update->assertRedirect();
        $update->assertSessionHas('error');
        // Original password must be completely untouched.
        $this->assertTrue(Hash::check('originalpassword', $admin->fresh()->password));
    }

    public function test_super_admin_with_existing_password_and_google_linked_also_hidden(): void
    {
        $superAdmin = $this->superAdmin([
            'google_id' => 'google-superadmin-789',
            'password'  => Hash::make('originalpassword'),
        ]);

        $response = $this->actingAs($superAdmin)->get(route('settings.index'));
        $response->assertOk();
        $response->assertDontSee('Change Password', false);
        $response->assertSee("there's no password to manage here", false);

        $update = $this->actingAs($superAdmin)
            ->patch(route('settings.password.update'), $this->passwordPayload('originalpassword'));

        $update->assertRedirect();
        $update->assertSessionHas('error');
        $this->assertTrue(Hash::check('originalpassword', $superAdmin->fresh()->password));
    }

    // ─── Applies identically across every role's settings page ────────

    public function test_teacher_google_linked_also_hidden(): void
    {
        $teacher = User::factory()->teacher()->create(['google_id' => 'google-teacher-1']);

        $response = $this->actingAs($teacher)->get(route('settings.index'));
        $response->assertOk();
        $response->assertDontSee('Change Password', false);
        $response->assertDontSee('Set Password', false);
        $response->assertSee("there's no password to manage here", false);
    }

    public function test_student_google_linked_also_hidden(): void
    {
        $student = User::factory()->student()->create(['google_id' => 'google-student-1']);

        $response = $this->actingAs($student)->get(route('settings.index'));
        $response->assertOk();
        $response->assertDontSee('Change Password', false);
        $response->assertDontSee('Set Password', false);
        $response->assertSee("there's no password to manage here", false);
    }

    public function test_teacher_without_google_still_sees_the_form(): void
    {
        $teacher = User::factory()->teacher()->create(['google_id' => null, 'password' => Hash::make('pw123456')]);

        $response = $this->actingAs($teacher)->get(route('settings.index'));
        $response->assertOk();
        $response->assertSee('Change Password');
    }
}
