<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // store() looks up the target role by name regardless of which role the acting
        // user holds — all four must exist up front, not just whichever role the acting
        // user in a given test happens to need.
        foreach (['super_admin', 'admin', 'teacher', 'student'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role_id'   => Role::where('name', 'super_admin')->first()->id,
            'is_active' => true,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id'   => Role::where('name', 'admin')->first()->id,
            'is_active' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'New Admin Person',
            'email'                 => 'newadmin@edunest.dev',
            'role'                  => 'admin',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    /** @test */
    public function test_super_admin_can_create_a_new_admin_account(): void
    {
        $superAdmin = $this->superAdmin();

        $response = $this->actingAs($superAdmin)->post(route('admin.users.store'), $this->payload());

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $newUser = User::where('email', 'newadmin@edunest.dev')->first();
        $this->assertNotNull($newUser);
        $this->assertSame('admin', $newUser->role->name);
        $this->assertTrue($newUser->is_active);
        $this->assertTrue(Hash::check('password123', $newUser->password));

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Created new admin account',
        ]);

        // New admin can immediately log in with the password that was set.
        $login = $this->post(route('auth.login'), [
            'email'    => 'newadmin@edunest.dev',
            'password' => 'password123',
        ]);
        $login->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($newUser);
    }

    /** @test */
    public function test_regular_admin_crafted_request_with_role_admin_is_rejected_and_creates_no_user(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $this->payload());

        // This app's bootstrap/app.php redirects AuthorizationException to a flash error
        // instead of a raw 403 (established in prior work on this project) — what matters
        // is that no user was created, not the exact status code.
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['email' => 'newadmin@edunest.dev']);
        $this->assertDatabaseMissing('activity_log', [
            'description' => 'Created new admin account',
        ]);
    }

    /** @test */
    public function test_regular_admin_create_form_does_not_show_admin_role_option(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('<option value="teacher">Teacher</option>', false);
        $response->assertSee('<option value="student">Student</option>', false);
        $response->assertDontSee('<option value="admin">Admin</option>', false);
    }

    /** @test */
    public function test_super_admin_create_form_shows_admin_role_option(): void
    {
        $superAdmin = $this->superAdmin();

        $response = $this->actingAs($superAdmin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('<option value="admin">Admin</option>', false);
    }

    /** @test */
    public function test_teacher_creation_via_shared_form_still_works_for_regular_admin(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $this->payload([
            'email' => 'newteacher@edunest.dev',
            'role'  => 'teacher',
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $newUser = User::where('email', 'newteacher@edunest.dev')->first();
        $this->assertNotNull($newUser);
        $this->assertSame('teacher', $newUser->role->name);

        // Regular teacher/student creation is not privilege-sensitive in the same way —
        // no "Created new admin account" log for this one.
        $this->assertDatabaseMissing('activity_log', [
            'description' => 'Created new admin account',
        ]);
    }

    /** @test */
    public function test_student_creation_via_shared_form_still_works_for_super_admin(): void
    {
        $superAdmin = $this->superAdmin();

        $response = $this->actingAs($superAdmin)->post(route('admin.users.store'), $this->payload([
            'email' => 'newstudent@edunest.dev',
            'role'  => 'student',
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $newUser = User::where('email', 'newstudent@edunest.dev')->first();
        $this->assertNotNull($newUser);
        $this->assertSame('student', $newUser->role->name);
    }
}
