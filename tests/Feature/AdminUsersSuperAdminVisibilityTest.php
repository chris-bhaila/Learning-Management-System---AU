<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersSuperAdminVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role_id'   => Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ]);
    }

    private function superAdmin(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role_id'   => Role::firstOrCreate(['name' => 'super_admin'])->id,
            'is_active' => true,
        ], $attrs));
    }

    public function test_regular_admin_does_not_see_super_admin_in_admins_tab(): void
    {
        $admin      = $this->admin();
        $superAdmin = $this->superAdmin(['name' => 'Sam Super']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'admin']));

        $response->assertOk();
        $users = $response->viewData('users');

        $this->assertFalse($users->contains(fn($u) => $u->id === $superAdmin->id));
    }

    public function test_super_admin_sees_other_super_admins_in_admins_tab(): void
    {
        $viewer     = $this->superAdmin(['name' => 'Sam Super']);
        $otherSuper = $this->superAdmin(['name' => 'Robin Root', 'email' => 'robin.root@edunest.dev']);
        $admin      = $this->admin();

        $response = $this->actingAs($viewer)->get(route('admin.users.index', ['role' => 'admin']));

        $response->assertOk();
        $users = $response->viewData('users');

        $this->assertTrue($users->contains(fn($u) => $u->id === $otherSuper->id));
        $this->assertTrue($users->contains(fn($u) => $u->id === $admin->id));
        // Super admin sees their own row too, same as a regular admin sees themselves today.
        $this->assertTrue($users->contains(fn($u) => $u->id === $viewer->id));
    }

    public function test_super_admin_viewing_admins_tab_admin_count_includes_super_admins(): void
    {
        $viewer = $this->superAdmin(['name' => 'Sam Super']);
        $this->superAdmin(['name' => 'Robin Root', 'email' => 'robin.root@edunest.dev']);
        $this->admin();

        $response = $this->actingAs($viewer)->get(route('admin.users.index', ['role' => 'admin']));

        $response->assertOk();
        $roleCounts = $response->viewData('roleCounts');

        // 1 regular admin + viewer (super_admin) + 1 other super_admin = 3
        $this->assertSame(3, $roleCounts['admin']);
    }

    public function test_regular_admin_role_counts_unaffected_by_super_admins(): void
    {
        $admin = $this->admin();
        $this->superAdmin(['name' => 'Sam Super']);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['role' => 'admin']));

        $response->assertOk();
        $roleCounts = $response->viewData('roleCounts');

        $this->assertSame(1, $roleCounts['admin']);
    }

    public function test_super_admin_can_view_another_super_admins_profile_page(): void
    {
        $viewer     = $this->superAdmin(['name' => 'Sam Super']);
        $otherSuper = $this->superAdmin(['name' => 'Robin Root', 'email' => 'robin.root@edunest.dev']);

        $response = $this->actingAs($viewer)->get(route('admin.users.show', $otherSuper->id));

        $response->assertOk();
        $response->assertSee('Robin Root');
        $response->assertSee('Super Admin');
    }

    public function test_super_admin_cannot_update_another_super_admin(): void
    {
        $viewer     = $this->superAdmin(['name' => 'Sam Super']);
        $otherSuper = $this->superAdmin(['name' => 'Robin Root', 'email' => 'robin.root@edunest.dev']);

        $response = $this->actingAs($viewer)->patch(route('admin.users.update', $otherSuper->id), [
            'name'      => 'Renamed By Attacker',
            'role'      => 'admin',
            'is_active' => 1,
        ]);

        // App-wide exception handler (bootstrap/app.php) converts a 403 AuthorizationException
        // into a redirect + flashed error for non-JSON requests, rather than a raw 403 body.
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('Robin Root', $otherSuper->fresh()->name);
    }

    public function test_super_admin_cannot_delete_another_super_admin(): void
    {
        $viewer     = $this->superAdmin(['name' => 'Sam Super']);
        $otherSuper = $this->superAdmin(['name' => 'Robin Root', 'email' => 'robin.root@edunest.dev']);

        $response = $this->actingAs($viewer)->delete(route('admin.users.destroy', $otherSuper->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotNull(User::find($otherSuper->id));
    }

    public function test_super_admin_can_still_update_their_own_row(): void
    {
        $viewer = $this->superAdmin(['name' => 'Sam Super']);

        $response = $this->actingAs($viewer)->patch(route('admin.users.update', $viewer->id), [
            'name'      => 'Sam Super Updated',
            'role'      => 'admin',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertSame('Sam Super Updated', $viewer->fresh()->name);
    }

    public function test_super_admin_cannot_deactivate_their_own_account(): void
    {
        $viewer = $this->superAdmin(['name' => 'Sam Super']);

        $response = $this->actingAs($viewer)->patch(route('admin.users.update', $viewer->id), [
            'name'      => 'Sam Super',
            'role'      => 'admin',
            'is_active' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertTrue($viewer->fresh()->is_active);
    }

    public function test_super_admin_can_still_deactivate_a_different_super_admin_is_active_field_blocked_but_edit_itself_forbidden(): void
    {
        // Sanity check: deactivating a DIFFERENT super_admin is already fully blocked by
        // the update() policy (tested above) — this self-deactivation guard is specifically
        // for the one case update() allows a super_admin to touch: their own row.
        $viewer     = $this->superAdmin(['name' => 'Sam Super']);
        $otherSuper = $this->superAdmin(['name' => 'Robin Root', 'email' => 'robin.root@edunest.dev']);

        $response = $this->actingAs($viewer)->patch(route('admin.users.update', $otherSuper->id), [
            'name'      => 'Robin Root',
            'role'      => 'admin',
            'is_active' => 0,
        ]);

        $response->assertRedirect();
        $this->assertTrue($otherSuper->fresh()->is_active);
    }

    public function test_super_admin_cannot_delete_themselves(): void
    {
        $viewer = $this->superAdmin(['name' => 'Sam Super']);

        $response = $this->actingAs($viewer)->delete(route('admin.users.destroy', $viewer->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotNull(User::find($viewer->id));
    }

    public function test_regular_admin_can_still_deactivate_themselves(): void
    {
        // The self-deactivation guard is Super Admin-specific — a regular Admin
        // deactivating their own account is pre-existing, unchanged behavior.
        $admin = $this->admin();

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $admin->id), [
            'name'      => $admin->name,
            'role'      => 'admin',
            'is_active' => 0,
        ]);

        $response->assertRedirect();
        $this->assertFalse($admin->fresh()->is_active);
    }

    public function test_regular_admin_cannot_update_or_delete_a_super_admin_via_crafted_request(): void
    {
        $admin      = $this->admin();
        $superAdmin = $this->superAdmin(['name' => 'Sam Super']);

        $updateResponse = $this->actingAs($admin)->patch(route('admin.users.update', $superAdmin->id), [
            'name'      => 'Renamed By Attacker',
            'role'      => 'admin',
            'is_active' => 1,
        ]);
        $updateResponse->assertRedirect();
        $this->assertSame('Sam Super', $superAdmin->fresh()->name);

        $deleteResponse = $this->actingAs($admin)->delete(route('admin.users.destroy', $superAdmin->id));
        $deleteResponse->assertRedirect();
        $this->assertNotNull(User::find($superAdmin->id));
    }

    public function test_edit_and_delete_buttons_hidden_for_other_super_admin_row(): void
    {
        $viewer     = $this->superAdmin(['name' => 'Sam Super']);
        $otherSuper = $this->superAdmin(['name' => 'Robin Root', 'email' => 'robin.root@edunest.dev']);

        $response = $this->actingAs($viewer)->get(route('admin.users.index', ['role' => 'admin']));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringNotContainsString("openEditModal({$otherSuper->id}", $html);
        $this->assertStringNotContainsString("openDeleteModal({$otherSuper->id}", $html);
        // Viewer's own row still has Edit, but never Delete — a Super Admin can never
        // delete themselves either (UserPolicy::delete() never exempts self).
        $this->assertStringContainsString("openEditModal({$viewer->id}", $html);
        $this->assertStringNotContainsString("openDeleteModal({$viewer->id}", $html);
    }
}
