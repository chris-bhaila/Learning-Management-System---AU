<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KickStudentTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function teacher(): User
    {
        return User::factory()->teacher()->create();
    }

    private function student(): User
    {
        return User::factory()->student()->create();
    }

    private function course(User $teacher): Course
    {
        return Course::factory()->for($teacher, 'teacher')->create();
    }

    private function enrollInClass(User $student, User $teacher): void
    {
        $student->teachers()->attach($teacher->id, ['is_active' => true, 'enrolled_at' => now()]);
    }

    private function enrollInCourse(User $student, Course $course): void
    {
        $student->enrolledCourses()->attach($course->id, ['is_active' => true, 'enrolled_at' => now()]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /** @test */
    public function test_kicking_from_class_deactivates_class_and_all_that_teachers_courses_only(): void
    {
        $teacher       = $this->teacher();
        $otherTeacher  = $this->teacher();
        $student       = $this->student();

        $courseA = $this->course($teacher);
        $courseB = $this->course($teacher);
        $otherTeacherCourse = $this->course($otherTeacher);

        $this->enrollInClass($student, $teacher);
        $this->enrollInClass($student, $otherTeacher); // student independently in a second teacher's class
        $this->enrollInCourse($student, $courseA);
        $this->enrollInCourse($student, $courseB);
        $this->enrollInCourse($student, $otherTeacherCourse);

        $response = $this->actingAs($teacher)->patch(route('teacher.students.kick', $student->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Class relationship with the kicking teacher is deactivated.
        $this->assertDatabaseHas('teacher_student', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'is_active'  => false,
        ]);

        // Every course belonging to that teacher is deactivated.
        $this->assertDatabaseHas('course_student', [
            'course_id'  => $courseA->id,
            'student_id' => $student->id,
            'is_active'  => false,
        ]);
        $this->assertDatabaseHas('course_student', [
            'course_id'  => $courseB->id,
            'student_id' => $student->id,
            'is_active'  => false,
        ]);

        // The OTHER teacher's class relationship and course are entirely untouched.
        $this->assertDatabaseHas('teacher_student', [
            'teacher_id' => $otherTeacher->id,
            'student_id' => $student->id,
            'is_active'  => true,
        ]);
        $this->assertDatabaseHas('course_student', [
            'course_id'  => $otherTeacherCourse->id,
            'student_id' => $student->id,
            'is_active'  => true,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Teacher kicked student from class',
        ]);
    }

    /** @test */
    public function test_removing_from_single_course_does_not_touch_class_or_other_courses(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $courseA = $this->course($teacher);
        $courseB = $this->course($teacher);

        $this->enrollInClass($student, $teacher);
        $this->enrollInCourse($student, $courseA);
        $this->enrollInCourse($student, $courseB);

        $response = $this->actingAs($teacher)
            ->patch(route('teacher.courses.students.remove', [$courseA->id, $student->id]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('course_student', [
            'course_id'  => $courseA->id,
            'student_id' => $student->id,
            'is_active'  => false,
        ]);

        // Course B and the class relationship are untouched.
        $this->assertDatabaseHas('course_student', [
            'course_id'  => $courseB->id,
            'student_id' => $student->id,
            'is_active'  => true,
        ]);
        $this->assertDatabaseHas('teacher_student', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'is_active'  => true,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Teacher removed student from course',
        ]);
    }

    /** @test */
    public function test_non_owning_teacher_cannot_kick_another_teachers_student(): void
    {
        $owningTeacher = $this->teacher();
        $otherTeacher  = $this->teacher();
        $student       = $this->student();

        $this->enrollInClass($student, $owningTeacher);

        // Real HTTP round trip — routing, middleware, FormRequest/controller, Policy —
        // nothing bypassed, per the standard established earlier in this project.
        $response = $this->actingAs($otherTeacher)->patch(route('teacher.students.kick', $student->id));

        // This app's bootstrap/app.php redirects AuthorizationException to a flash error
        // instead of a raw 403 (see prior verification in this project) — what matters is
        // the mutation did not happen.
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('teacher_student', [
            'teacher_id' => $owningTeacher->id,
            'student_id' => $student->id,
            'is_active'  => true,
        ]);

        $this->assertDatabaseMissing('activity_log', [
            'description' => 'Teacher kicked student from class',
        ]);
    }

    /** @test */
    public function test_non_owning_teacher_cannot_remove_student_from_another_teachers_course(): void
    {
        $owningTeacher = $this->teacher();
        $otherTeacher  = $this->teacher();
        $student       = $this->student();
        $course        = $this->course($owningTeacher);

        $this->enrollInClass($student, $owningTeacher);
        $this->enrollInCourse($student, $course);

        $response = $this->actingAs($otherTeacher)
            ->patch(route('teacher.courses.students.remove', [$course->id, $student->id]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('course_student', [
            'course_id'  => $course->id,
            'student_id' => $student->id,
            'is_active'  => true,
        ]);
    }

    /** @test */
    public function test_kicked_student_can_rejoin_via_fresh_token_without_duplicate_pivot_row(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();

        $this->enrollInClass($student, $teacher);
        $this->assertDatabaseCount('teacher_student', 1);

        // Kick.
        $this->actingAs($teacher)->patch(route('teacher.students.kick', $student->id));
        $this->assertDatabaseHas('teacher_student', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'is_active'  => false,
        ]);
        $this->assertDatabaseCount('teacher_student', 1); // still just the one row

        // Rejoin with a fresh token from the same teacher.
        $token = Token::factory()->for($teacher, 'teacher')->create();
        $response = $this->actingAs($student)
            ->post(route('student.enroll'), ['token_value' => $token->token_value]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Same row, reactivated — NOT a second row.
        $this->assertDatabaseCount('teacher_student', 1);
        $this->assertDatabaseHas('teacher_student', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'is_active'  => true,
        ]);
        $this->assertDatabaseHas('tokens', [
            'id'         => $token->id,
            'uses_count' => 1,
        ]);
    }

    /** @test */
    public function test_kicked_student_can_rejoin_course_via_fresh_token_without_duplicate_pivot_row(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);

        $this->enrollInClass($student, $teacher);
        $this->enrollInCourse($student, $course);
        $this->assertDatabaseCount('course_student', 1);

        // Remove from course.
        $this->actingAs($teacher)
            ->patch(route('teacher.courses.students.remove', [$course->id, $student->id]));
        $this->assertDatabaseHas('course_student', [
            'course_id'  => $course->id,
            'student_id' => $student->id,
            'is_active'  => false,
        ]);
        $this->assertDatabaseCount('course_student', 1);

        // Rejoin via a fresh course token.
        $token = Token::factory()->forCourse($course)->create();
        $response = $this->actingAs($student)
            ->post(route('student.enroll'), ['token_value' => $token->token_value]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseCount('course_student', 1);
        $this->assertDatabaseHas('course_student', [
            'course_id'  => $course->id,
            'student_id' => $student->id,
            'is_active'  => true,
        ]);
    }

    /** @test */
    public function test_admin_can_kick_student_from_a_teachers_class(): void
    {
        $admin = User::factory()->create([
            'role_id'   => Role::firstOrCreate(['name' => 'admin'])->id,
            'is_active' => true,
        ]);
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);

        $this->enrollInClass($student, $teacher);
        $this->enrollInCourse($student, $course);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.classes.kick', [$student->id, $teacher->id]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('teacher_student', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'is_active'  => false,
        ]);
        $this->assertDatabaseHas('course_student', [
            'course_id'  => $course->id,
            'student_id' => $student->id,
            'is_active'  => false,
        ]);
    }
}
