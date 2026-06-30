<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
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

    private function classToken(User $teacher, array $overrides = []): Token
    {
        return Token::factory()
            ->for($teacher, 'teacher')
            ->create($overrides);
    }

    private function courseToken(User $teacher, Course $course, array $overrides = []): Token
    {
        return Token::factory()
            ->forCourse($course)
            ->create($overrides);
    }

    private function enroll(User $student, string $tokenValue)
    {
        return $this->actingAs($student)
            ->post(route('student.enroll'), ['token_value' => $tokenValue]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    /** @test */
    public function test_student_can_join_teacher_class_with_valid_class_token(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $token   = $this->classToken($teacher);

        $response = $this->enroll($student, $token->token_value);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

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
    public function test_student_can_enroll_in_course_after_joining_class(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $course  = $this->course($teacher);

        // First, join the class
        $classToken  = $this->classToken($teacher);
        $this->enroll($student, $classToken->token_value);

        // Now enroll in the course
        $courseToken = $this->courseToken($teacher, $course);
        $response    = $this->enroll($student, $courseToken->token_value);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('course_student', [
            'course_id'  => $course->id,
            'student_id' => $student->id,
            'is_active'  => true,
        ]);

        $this->assertDatabaseHas('tokens', [
            'id'         => $courseToken->id,
            'uses_count' => 1,
        ]);
    }

    /** @test */
    public function test_student_cannot_use_course_token_without_joining_class_first(): void
    {
        $teacher     = $this->teacher();
        $student     = $this->student();
        $course      = $this->course($teacher);
        $courseToken = $this->courseToken($teacher, $course);

        $response = $this->enroll($student, $courseToken->token_value);

        $response->assertSessionHasErrors('token_value');
        $response->assertSessionHasErrors(['token_value' => "You must join the teacher's class before enrolling in a course."]);

        $this->assertDatabaseMissing('course_student', [
            'course_id'  => $course->id,
            'student_id' => $student->id,
        ]);

        // uses_count must not have changed
        $this->assertDatabaseHas('tokens', [
            'id'         => $courseToken->id,
            'uses_count' => 0,
        ]);
    }

    /** @test */
    public function test_expired_token_is_rejected(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $token   = $this->classToken($teacher, ['expires_at' => now()->subMinute()]);

        $response = $this->enroll($student, $token->token_value);

        $response->assertSessionHasErrors('token_value');
        $response->assertSessionHasErrors(['token_value' => 'This token is invalid or has expired.']);

        $this->assertDatabaseMissing('teacher_student', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
        ]);

        $this->assertDatabaseHas('tokens', [
            'id'         => $token->id,
            'uses_count' => 0,
        ]);
    }

    /** @test */
    public function test_token_at_max_uses_is_rejected(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $token   = $this->classToken($teacher, ['max_uses' => 1, 'uses_count' => 1]);

        $response = $this->enroll($student, $token->token_value);

        $response->assertSessionHasErrors('token_value');
        $response->assertSessionHasErrors(['token_value' => 'This token is invalid or has expired.']);

        $this->assertDatabaseMissing('teacher_student', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
        ]);

        // uses_count must stay at 1, not increment further
        $this->assertDatabaseHas('tokens', [
            'id'         => $token->id,
            'uses_count' => 1,
        ]);
    }

    /** @test */
    public function test_student_cannot_enroll_twice_with_same_class_token(): void
    {
        $teacher = $this->teacher();
        $student = $this->student();
        $token   = $this->classToken($teacher, ['max_uses' => 30]);

        // First enrollment — succeeds
        $this->enroll($student, $token->token_value);

        // Second attempt — rejected
        $response = $this->enroll($student, $token->token_value);

        $response->assertSessionHasErrors('token_value');
        $response->assertSessionHasErrors(['token_value' => 'You are already enrolled in this class.']);

        // Only one pivot row
        $this->assertDatabaseCount('teacher_student', 1);

        // uses_count must be exactly 1, not 2
        $this->assertDatabaseHas('tokens', [
            'id'         => $token->id,
            'uses_count' => 1,
        ]);
    }

    /** @test */
    public function test_invalid_token_value_is_rejected(): void
    {
        $student = $this->student();

        $response = $this->enroll($student, 'DOESNOTEXIST');

        $response->assertSessionHasErrors('token_value');
        $response->assertSessionHasErrors(['token_value' => 'This token is invalid or has expired.']);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_enroll(): void
    {
        $response = $this->post(route('student.enroll'), ['token_value' => 'ABCDEFGHI']);

        $response->assertRedirect(route('login'));
    }
}
