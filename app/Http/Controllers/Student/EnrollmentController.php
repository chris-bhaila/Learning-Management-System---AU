<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\EnrollRequest;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    public function __construct(
        private TokenRepositoryInterface $tokens,
        private UserRepositoryInterface $users,
        private CourseRepositoryInterface $courses,
    ) {}

    public function store(EnrollRequest $request)
    {
        $attemptedValue = $request->validated('token_value');
        $student        = Auth::user();
        $token          = $this->tokens->findByValue($attemptedValue);

        if (!$token) {
            activity()
                ->causedBy($student)
                ->withProperties([
                    'token_value' => $attemptedValue,
                    'reason'      => 'not_found',
                ])
                ->log('Student enrollment failed: token not found');

            return back()
                ->withErrors(['token_value' => "That token doesn't exist. Double-check the code your teacher gave you."])
                ->withInput();
        }

        if ($token->isExpired()) {
            activity()
                ->causedBy($student)
                ->withProperties([
                    'token_value'  => $token->token_value,
                    'token_type'   => $token->type,
                    'teacher_id'   => $token->teacher_id,
                    'teacher_name' => $token->teacher?->name ?? 'Unknown',
                    'reason'       => 'expired',
                ])
                ->log('Student enrollment failed: token expired');

            return back()
                ->withErrors(['token_value' => 'That token has expired. Ask your teacher to generate a new one.'])
                ->withInput();
        }

        $teacher = $token->teacher;

        if ($token->isClassToken()) {
            $alreadyEnrolled = $student->teachers()
                ->where('teacher_student.teacher_id', $token->teacher_id)
                ->exists();

            if ($alreadyEnrolled) {
                activity()
                    ->causedBy($student)
                    ->withProperties([
                        'token_value'  => $token->token_value,
                        'token_type'   => 'class',
                        'teacher_id'   => $token->teacher_id,
                        'teacher_name' => $teacher?->name ?? 'Unknown',
                        'reason'       => 'already_in_class',
                    ])
                    ->log('Student enrollment failed: already in class');

                return back()
                    ->withErrors(['token_value' => "You're already enrolled in {$teacher?->name}'s class."])
                    ->withInput();
            }

            $student->teachers()->attach($token->teacher_id, [
                'is_active'   => true,
                'enrolled_at' => now(),
            ]);

            activity()
                ->causedBy($student)
                ->withProperties([
                    'token_value'  => $token->token_value,
                    'token_type'   => 'class',
                    'teacher_id'   => $token->teacher_id,
                    'teacher_name' => $teacher?->name ?? 'Unknown',
                ])
                ->log('Student joined teacher class via class token');

            $this->tokens->incrementUses($token);

            return back()
                ->with('enroll_success', "You've joined {$teacher?->name}'s class!")
                ->withInput(['_modal' => 'enroll']);
        }

        // Course token — must be enrolled in teacher's class first
        $inClass = $student->teachers()
            ->where('teacher_student.teacher_id', $token->teacher_id)
            ->where('teacher_student.is_active', true)
            ->exists();

        if (!$inClass) {
            activity()
                ->causedBy($student)
                ->withProperties([
                    'token_value'  => $token->token_value,
                    'token_type'   => 'course',
                    'teacher_id'   => $token->teacher_id,
                    'teacher_name' => $teacher?->name ?? 'Unknown',
                    'course_id'    => $token->course_id,
                    'reason'       => 'not_in_class',
                ])
                ->log('Student enrollment failed: not in teacher class');

            return back()
                ->withErrors(['token_value' => "You need to join {$teacher?->name}'s class before enrolling in a course. Use your 9-character class token first."])
                ->withInput();
        }

        $alreadyEnrolled = $student->enrolledCourses()
            ->where('course_student.course_id', $token->course_id)
            ->exists();

        if ($alreadyEnrolled) {
            $course = $token->course;

            activity()
                ->causedBy($student)
                ->withProperties([
                    'token_value'  => $token->token_value,
                    'token_type'   => 'course',
                    'teacher_id'   => $token->teacher_id,
                    'teacher_name' => $teacher?->name ?? 'Unknown',
                    'course_id'    => $token->course_id,
                    'course_title' => $course?->title ?? 'Unknown',
                    'reason'       => 'already_enrolled',
                ])
                ->log('Student enrollment failed: already enrolled in course');

            return back()
                ->withErrors(['token_value' => "You're already enrolled in {$course?->title}."])
                ->withInput();
        }

        $course = $token->course;

        $student->enrolledCourses()->attach($token->course_id, [
            'is_active'   => true,
            'enrolled_at' => now(),
        ]);

        activity()
            ->causedBy($student)
            ->withProperties([
                'token_value'  => $token->token_value,
                'token_type'   => 'course',
                'teacher_id'   => $token->teacher_id,
                'teacher_name' => $teacher?->name ?? 'Unknown',
                'course_id'    => $token->course_id,
                'course_title' => $course?->title ?? 'Unknown',
            ])
            ->log('Student enrolled in course via course token');

        $this->tokens->incrementUses($token);

        return back()
            ->with('enroll_success', "You've enrolled in {$course?->title}!")
            ->withInput(['_modal' => 'enroll']);
    }
}
