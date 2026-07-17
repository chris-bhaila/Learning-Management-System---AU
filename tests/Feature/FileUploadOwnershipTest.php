<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\File;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the ownership check added to FileController::store() — previously any
 * authenticated teacher could attach a file to ANY course/unit by guessing the
 * fileable_id, regardless of who owned it. Mirrors the ownership pattern already
 * used by Teacher\TokenController::store() (CoursePolicy::update()/UnitPolicy::update()).
 */
class FileUploadOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role_id'   => \App\Models\Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ]);
    }

    private function course(User $teacher): Course
    {
        return Course::factory()->for($teacher, 'teacher')->create();
    }

    public function test_teacher_cannot_attach_file_to_another_teachers_course(): void
    {
        Storage::fake('private');

        $teacherA = $this->teacher();
        $teacherB = $this->teacher();
        $courseB  = $this->course($teacherB);

        $response = $this->actingAs($teacherA)->post(route('files.store'), [
            'file'          => UploadedFile::fake()->create('sneaky.pdf', 10, 'application/pdf'),
            'fileable_type' => Course::class,
            'fileable_id'   => $courseB->id,
        ]);

        // This app's bootstrap/app.php redirects AuthorizationException to a flash error
        // instead of a raw 403 (same convention already established elsewhere in this
        // project's tests) — what matters is the write did not happen.
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, File::count());
    }

    public function test_teacher_cannot_attach_file_to_another_teachers_unit(): void
    {
        Storage::fake('private');

        $teacherA = $this->teacher();
        $teacherB = $this->teacher();
        $courseB  = $this->course($teacherB);
        $unitB    = Unit::create([
            'course_id' => $courseB->id,
            'title'     => 'Someone Else\'s Unit',
            'content'   => 'content',
            'order'     => 1,
        ]);

        $response = $this->actingAs($teacherA)->post(route('files.store'), [
            'file'          => UploadedFile::fake()->create('sneaky.pdf', 10, 'application/pdf'),
            'fileable_type' => Unit::class,
            'fileable_id'   => $unitB->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, File::count());
    }

    public function test_teacher_can_attach_file_to_their_own_course(): void
    {
        Storage::fake('private');

        $teacher = $this->teacher();
        $course  = $this->course($teacher);

        $response = $this->actingAs($teacher)->post(route('files.store'), [
            'file'          => UploadedFile::fake()->create('mine.pdf', 10, 'application/pdf'),
            'fileable_type' => Course::class,
            'fileable_id'   => $course->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertSame(1, File::count());
    }

    public function test_admin_bypasses_ownership_check(): void
    {
        Storage::fake('private');

        $admin   = $this->admin();
        $teacher = $this->teacher();
        $course  = $this->course($teacher);

        $response = $this->actingAs($admin)->post(route('files.store'), [
            'file'          => UploadedFile::fake()->create('admin-upload.pdf', 10, 'application/pdf'),
            'fileable_type' => Course::class,
            'fileable_id'   => $course->id,
        ]);

        $response->assertRedirect();
        $this->assertSame(1, File::count());
    }

    public function test_nonexistent_fileable_id_returns_404_not_403(): void
    {
        Storage::fake('private');

        $teacher = $this->teacher();

        $response = $this->actingAs($teacher)->post(route('files.store'), [
            'file'          => UploadedFile::fake()->create('ghost.pdf', 10, 'application/pdf'),
            'fileable_type' => Course::class,
            'fileable_id'   => 999999,
        ]);

        $response->assertNotFound();
        $this->assertSame(0, File::count());
    }
}
