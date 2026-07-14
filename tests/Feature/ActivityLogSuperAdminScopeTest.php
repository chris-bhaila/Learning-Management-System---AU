<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogSuperAdminScopeTest extends TestCase
{
    use RefreshDatabase;

    // Fixture user creation goes through User's LogsActivity trait and would otherwise
    // add its own "created" activity rows (causer-less, since no HTTP request is acting
    // at factory-creation time) — noise unrelated to what these tests are verifying.
    // Disabling logging around fixture setup keeps each test's activity rows limited to
    // the ones it explicitly logs via activity()->causedBy(...).
    private function admin(): User
    {
        activity()->disableLogging();
        $user = User::factory()->create([
            'role_id'   => Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ]);
        activity()->enableLogging();

        return $user;
    }

    private function superAdmin(): User
    {
        activity()->disableLogging();
        $user = User::factory()->create([
            'name'      => 'Sam Super',
            'email'     => 'sam.super@edunest.dev',
            'role_id'   => Role::firstOrCreate(['name' => 'super_admin'])->id,
            'is_active' => true,
        ]);
        activity()->enableLogging();

        return $user;
    }

    public function test_regular_admin_never_sees_rows_caused_by_super_admin_on_screen(): void
    {
        $admin      = $this->admin();
        $superAdmin = $this->superAdmin();

        activity()->causedBy($superAdmin)->event('updated')->log('Promoted a teacher to admin');
        activity()->causedBy($admin)->event('created')->log('Created a course');

        $response = $this->actingAs($admin)->get(route('admin.logs.index'));

        $response->assertOk();
        $logs = $response->viewData('logs');

        $this->assertTrue($logs->every(fn($log) => $log->causer_id !== $superAdmin->id));
        $this->assertSame(1, $logs->count());
    }

    public function test_regular_admin_never_sees_super_admin_rows_regardless_of_applied_filters(): void
    {
        $admin      = $this->admin();
        $superAdmin = $this->superAdmin();

        activity()->causedBy($superAdmin)->event('created')->log('Promoted a teacher to admin');
        activity()->causedBy($admin)->event('created')->log('Created a course');

        foreach ([
            ['event' => 'created'],
            ['date' => 'today'],
            ['search' => 'Super'],
        ] as $filters) {
            $response = $this->actingAs($admin)->get(route('admin.logs.index', $filters));
            $response->assertOk();
            $logs = $response->viewData('logs');
            $this->assertTrue(
                $logs->every(fn($log) => $log->causer_id !== $superAdmin->id),
                'Leaked a super_admin row with filters: ' . json_encode($filters)
            );
        }
    }

    public function test_regular_admin_searching_for_super_admins_name_gets_zero_results_not_an_error(): void
    {
        $admin      = $this->admin();
        $superAdmin = $this->superAdmin();

        activity()->causedBy($superAdmin)->event('login')->log('Signed in');

        $response = $this->actingAs($admin)->get(route('admin.logs.index', ['search' => 'Sam Super']));

        $response->assertOk();
        $this->assertSame(0, $response->viewData('logs')->count());
    }

    public function test_causer_less_system_rows_remain_visible_to_regular_admin(): void
    {
        $admin = $this->admin();

        activity()->withProperties(['note' => 'system event'])->event('created')->log('System-caused row');

        $response = $this->actingAs($admin)->get(route('admin.logs.index'));

        $response->assertOk();
        $logs = $response->viewData('logs');

        $this->assertSame(1, $logs->count());
        $this->assertNull($logs->first()->causer_id);
    }

    public function test_email_search_still_works_for_regular_admin_layered_on_top_of_the_super_admin_scope(): void
    {
        $admin      = $this->admin();
        $superAdmin = $this->superAdmin();
        activity()->disableLogging();
        $teacher = User::factory()->teacher()->create(['name' => 'Casey Vale', 'email' => 'casey.vale@edunest.dev']);
        activity()->enableLogging();

        activity()->causedBy($teacher)->event('login')->log('Signed in');
        activity()->causedBy($superAdmin)->event('login')->log('Signed in');

        $response = $this->actingAs($admin)->get(route('admin.logs.index', ['search' => 'casey.vale']));

        $response->assertOk();
        $logs = $response->viewData('logs');
        $this->assertSame(1, $logs->count());
        $this->assertSame($teacher->id, $logs->first()->causer_id);
    }

    public function test_regular_admin_csv_export_contains_zero_rows_caused_by_super_admin(): void
    {
        $admin      = $this->admin();
        $superAdmin = $this->superAdmin();

        activity()->causedBy($superAdmin)->event('updated')->log('Promoted a teacher to admin');
        activity()->causedBy($admin)->event('created')->log('Created a course');

        $response = $this->actingAs($admin)->get(route('admin.logs.export'));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringNotContainsString('Sam Super', $csv);
        $this->assertStringNotContainsString('sam.super@edunest.dev', $csv);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csv);
        rewind($stream);
        $records = [];
        while (($record = fgetcsv($stream)) !== false) {
            $records[] = $record;
        }
        fclose($stream);

        $this->assertSame(1, count($records) - 1); // header + 1 data row
    }

    public function test_super_admin_sees_their_own_actions_and_everyone_elses(): void
    {
        $superAdmin = $this->superAdmin();
        $admin      = $this->admin();

        activity()->causedBy($superAdmin)->event('updated')->log('Promoted a teacher to admin');
        activity()->causedBy($admin)->event('created')->log('Created a course');

        $response = $this->actingAs($superAdmin)->get(route('admin.logs.index'));

        $response->assertOk();
        $logs = $response->viewData('logs');

        $this->assertSame(2, $logs->count());
        $this->assertTrue($logs->contains(fn($log) => $log->causer_id === $superAdmin->id));
        $this->assertTrue($logs->contains(fn($log) => $log->causer_id === $admin->id));
    }

    public function test_super_admin_csv_export_includes_their_own_rows(): void
    {
        $superAdmin = $this->superAdmin();
        $admin      = $this->admin();

        activity()->causedBy($superAdmin)->event('updated')->log('Promoted a teacher to admin');
        activity()->causedBy($admin)->event('created')->log('Created a course');

        $response = $this->actingAs($superAdmin)->get(route('admin.logs.export'));

        $response->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Sam Super', $csv);
    }

    public function test_regular_admin_dashboard_recent_activity_widget_excludes_super_admin_rows(): void
    {
        $admin      = $this->admin();
        $superAdmin = $this->superAdmin();

        activity()->causedBy($superAdmin)->event('updated')->log('Promoted a teacher to admin');
        activity()->causedBy($admin)->event('created')->log('Created a course');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $recentActivity = $response->viewData('recentActivity');

        $this->assertTrue($recentActivity->every(fn($log) => $log->causer_id !== $superAdmin->id));
        $this->assertSame(1, $recentActivity->count());
    }

    public function test_super_admin_dashboard_recent_activity_widget_includes_their_own_rows(): void
    {
        $superAdmin = $this->superAdmin();
        $admin      = $this->admin();

        activity()->causedBy($superAdmin)->event('updated')->log('Promoted a teacher to admin');
        activity()->causedBy($admin)->event('created')->log('Created a course');

        $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $recentActivity = $response->viewData('recentActivity');

        $this->assertSame(2, $recentActivity->count());
        $this->assertTrue($recentActivity->contains(fn($log) => $log->causer_id === $superAdmin->id));
    }
}
