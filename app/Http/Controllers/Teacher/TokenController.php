<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTokenRequest;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class TokenController extends Controller
{
    public function __construct(
        private TokenRepositoryInterface $tokens,
        private CourseRepositoryInterface $courses,
    ) {}

    public function index()
    {
        $teacherId       = Auth::id();
        $allClassTokens  = $this->tokens->getClassTokensByTeacher($teacherId);
        $allCourseTokens = $this->tokens->getCourseTokensByTeacher($teacherId);

        return view('teacher.tokens.index', [
            'classTokens'       => $allClassTokens->take(3),
            'classTokensTotal'  => $allClassTokens->count(),
            'courseTokens'      => $allCourseTokens->take(3),
            'courseTokensTotal' => $allCourseTokens->count(),
            'courses'           => $this->courses->getByTeacher($teacherId),
        ]);
    }

    public function classTokens()
    {
        $teacherId = Auth::id();

        return view('teacher.tokens.class', [
            'tokens' => $this->tokens->getClassTokensByTeacherPaginated($teacherId, 20),
        ]);
    }

    public function courseTokens()
    {
        $teacherId = Auth::id();

        return view('teacher.tokens.course', [
            'tokens' => $this->tokens->getCourseTokensByTeacherPaginated($teacherId, 20),
        ]);
    }

    public function store(StoreTokenRequest $request)
    {
        $data = $request->validated();

        if ($data['type'] === 'course') {
            $course = $this->courses->find($data['course_id']);
            $this->authorize('update', $course);
        }

        $this->tokens->create([
            'teacher_id'  => Auth::id(),
            'course_id'   => $data['course_id'] ?? null,
            'token_value' => $this->tokens->generateUniqueValue($data['type']),
            'type'        => $data['type'],
            'expires_at'  => now()->addMinutes($request->lifetimeInMinutes()),
            'max_uses'    => $data['max_uses'],
            'uses_count'  => 0,
        ]);

        return back()->with('success', 'Token generated.');
    }

    public function usage(string $tokenValue)
    {
        $token = $this->tokens->findByValue($tokenValue);

        if ($token && $token->teacher_id !== Auth::id()) {
            abort(404);
        }

        $activities = Activity::where('properties->token_value', $tokenValue)
            ->with('causer')
            ->latest()
            ->get();

        if (!$token) {
            $belongsToTeacher = $activities->contains(
                fn($a) => (int) $a->properties->get('teacher_id') === Auth::id()
            );
            if (!$belongsToTeacher) {
                abort(404);
            }
        }

        return view('tokens.usage', [
            'tokenValue' => $tokenValue,
            'token'      => $token,
            'activities' => $activities,
            'layout'     => 'layouts.teacher',
            'backRoute'  => route('teacher.tokens.index'),
        ]);
    }

    public function revoke(int $id)
    {
        $token = $this->tokens->find($id);
        abort_if(is_null($token), 404);

        $this->authorize('revoke', $token);

        $this->tokens->revoke($token);

        return back()->with('success', 'Token revoked.');
    }
}