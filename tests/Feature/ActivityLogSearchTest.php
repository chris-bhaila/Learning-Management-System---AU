<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogSearchTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role_id'   => Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function test_search_by_shared_name_still_matches_both_name_collision_users(): void
    {
        $admin = $this->admin();

        $jordanA = User::factory()->teacher()->create(['name' => 'Jordan Reed', 'email' => 'jordan.reed.one@edunest.dev']);
        $jordanB = User::factory()->teacher()->create(['name' => 'Jordan Reed', 'email' => 'jordan.reed.two@edunest.dev']);

        activity()->causedBy($jordanA)->event('login')->log('Signed in');
        activity()->causedBy($jordanB)->event('login')->log('Signed in');

        $response = $this->actingAs($admin)->get(route('admin.logs.index', ['search' => 'Jordan Reed']));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('logs')->count());
    }

    /** @test */
    public function test_search_by_exact_email_disambiguates_name_collision(): void
    {
        $admin = $this->admin();

        $jordanA = User::factory()->teacher()->create(['name' => 'Jordan Reed', 'email' => 'jordan.reed.one@edunest.dev']);
        $jordanB = User::factory()->teacher()->create(['name' => 'Jordan Reed', 'email' => 'jordan.reed.two@edunest.dev']);

        activity()->causedBy($jordanA)->event('login')->log('Signed in');
        activity()->causedBy($jordanB)->event('login')->log('Signed in');

        $response = $this->actingAs($admin)->get(route('admin.logs.index', ['search' => 'jordan.reed.one@edunest.dev']));

        $response->assertOk();
        $logs = $response->viewData('logs');
        $this->assertSame(1, $logs->count());
        $this->assertSame($jordanA->id, $logs->first()->causer_id);
    }

    /** @test */
    public function test_search_by_partial_email_fragment_matches_like_partial_name_does(): void
    {
        $admin   = $this->admin();
        $teacher = User::factory()->teacher()->create(['name' => 'Casey Vale', 'email' => 'casey.vale@edunest.dev']);
        $noise   = User::factory()->teacher()->create(['name' => 'Unrelated Person', 'email' => 'unrelated@example.org']);

        activity()->causedBy($teacher)->event('login')->log('Signed in');
        activity()->causedBy($noise)->event('login')->log('Signed in');

        // Partial fragment from the middle of the email, not the full address.
        $response = $this->actingAs($admin)->get(route('admin.logs.index', ['search' => 'casey.vale']));

        $response->assertOk();
        $logs = $response->viewData('logs');
        $this->assertSame(1, $logs->count());
        $this->assertSame($teacher->id, $logs->first()->causer_id);
    }

    /** @test */
    public function test_export_with_email_search_matches_the_same_filtered_set(): void
    {
        $admin = $this->admin();

        $jordanA = User::factory()->teacher()->create(['name' => 'Jordan Reed', 'email' => 'jordan.reed.one@edunest.dev']);
        $jordanB = User::factory()->teacher()->create(['name' => 'Jordan Reed', 'email' => 'jordan.reed.two@edunest.dev']);

        activity()->causedBy($jordanA)->event('login')->log('Signed in');
        activity()->causedBy($jordanB)->event('login')->log('Signed in');

        $indexResponse = $this->actingAs($admin)->get(route('admin.logs.index', ['search' => 'jordan.reed.one@edunest.dev']));
        $visibleCount  = $indexResponse->viewData('logs')->count();

        $exportResponse = $this->actingAs($admin)->get(route('admin.logs.export', ['search' => 'jordan.reed.one@edunest.dev']));
        $exportResponse->assertOk();
        $csv = $exportResponse->streamedContent();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csv);
        rewind($stream);
        $records = [];
        while (($record = fgetcsv($stream)) !== false) {
            $records[] = $record;
        }
        fclose($stream);
        $dataRows = array_slice($records, 1);

        $this->assertSame(1, $visibleCount);
        $this->assertSame($visibleCount, count($dataRows));
        $this->assertStringContainsString('Jordan Reed', $csv);
    }
}
