<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\EnrollRequest;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    public function __construct(
        private TokenRepositoryInterface $tokens,
        private UserRepositoryInterface $users,
        private CourseRepositoryInterface $courses,
        private ActivityLogRepositoryInterface $logs,
    ) {}

    public function store(EnrollRequest $request)
    {
        $token = $this->tokens->findByValue($request->validated('token_value'));

        if (!$token || $token->isExpired()) {
            return back()->withErrors(['token_value' => 'This token is invalid or has expired.']);
        }

        $student = Auth::user();

        if ($token->isClassToken()) {
            // Check not already in this teacher's class
            $alreadyEnrolled = $student->teachers()
                ->where('teacher_id', $token->teacher_id)
                ->exists();

            if ($alreadyEnrolled) {
                return back()->withErrors(['token_value' => 'You are already enrolled in this class.']);
            }

            $student->teachers()->attach($token->teacher_id, [
                'is_active'   => true,
                'enrolled_at' => now(),
            ]);
        } else {
            // Course token — must be enrolled in teacher's class first
            $inClass = $student->teachers()
                ->where('teacher_id', $token->teacher_id)
                ->where('is_active', true)
                ->exists();

            if (!$inClass) {
                return back()->withErrors(['token_value' => 'You must join the teacher\'s class before enrolling in a course.']);
            }

            $alreadyEnrolled = $student->enrolledCourses()
                ->where('course_id', $token->course_id)
                ->exists();

            if ($alreadyEnrolled) {
                return back()->withErrors(['token_value' => 'You are already enrolled in this course.']);
            }

            $student->enrolledCourses()->attach($token->course_id, [
                'is_active'   => true,
                'enrolled_at' => now(),
            ]);
        }

        $this->tokens->incrementUses($token);

        $this->logs->create([
            'user_id'      => $student->id,
            'action'       => $token->isClassToken() ? 'joined_class' : 'enrolled_course',
            'subject_type' => $token->isClassToken() ? null : 'App\Models\Course',
            'subject_id'   => $token->isClassToken() ? null : $token->course_id,
            'metadata'     => ['token_type' => $token->type],
        ]);

        return back()->with('success', $token->isClassToken()
            ? 'You have joined the class.'
            : 'You have enrolled in the course.'
        );
    }
}