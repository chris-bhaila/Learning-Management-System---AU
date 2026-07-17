<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTokenRequest;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Spatie\Activitylog\Models\Activity;

class TokenController extends Controller
{
    public function __construct(
        private TokenRepositoryInterface $tokens,
        private UserRepositoryInterface $users,
        private CourseRepositoryInterface $courses,
    ) {}

    public function index()
    {
        $teachers   = $this->users->getAllTeachers();
        $teacherId  = request()->integer('teacher_id') ?: null;
        $teacher    = $teacherId ? $teachers->firstWhere('id', $teacherId) : null;

        $allClassTokens  = $teacher ? $this->tokens->getClassTokensByTeacher($teacher->id) : collect();
        $allCourseTokens = $teacher ? $this->tokens->getCourseTokensByTeacher($teacher->id) : collect();

        return view('admin.tokens.index', [
            'teachers'          => $teachers,
            'teacher'           => $teacher,
            'classTokens'       => $allClassTokens->take(3),
            'classTokensTotal'  => $allClassTokens->count(),
            'courseTokens'      => $allCourseTokens->take(3),
            'courseTokensTotal' => $allCourseTokens->count(),
            'courses'           => $teacher ? $this->courses->getByTeacher($teacher->id) : collect(),
        ]);
    }

    public function classTokens()
    {
        $teachers  = $this->users->getAllTeachers();
        $teacherId = request()->integer('teacher_id') ?: null;
        $teacher   = $teacherId ? $teachers->firstWhere('id', $teacherId) : null;

        return view('admin.tokens.class', [
            'teachers' => $teachers,
            'teacher'  => $teacher,
            'tokens'   => $teacher ? $this->tokens->getClassTokensByTeacherPaginated($teacher->id, 20) : null,
        ]);
    }

    public function courseTokens()
    {
        $teachers  = $this->users->getAllTeachers();
        $teacherId = request()->integer('teacher_id') ?: null;
        $teacher   = $teacherId ? $teachers->firstWhere('id', $teacherId) : null;

        return view('admin.tokens.course', [
            'teachers' => $teachers,
            'teacher'  => $teacher,
            'tokens'   => $teacher ? $this->tokens->getCourseTokensByTeacherPaginated($teacher->id, 20) : null,
        ]);
    }

    public function store(StoreTokenRequest $request)
    {
        $data = $request->validated();

        $this->tokens->create([
            'teacher_id'  => $data['teacher_id'],
            'course_id'   => $data['course_id'] ?? null,
            'token_value' => $this->tokens->generateUniqueValue($data['type']),
            'type'        => $data['type'],
            'expires_at'  => now()->addMinutes($request->lifetimeInMinutes()),
            'max_uses'    => $data['max_uses'],
            'uses_count'  => 0,
        ]);

        return redirect()->route('admin.tokens.index', ['teacher_id' => $data['teacher_id']])
            ->with('success', 'Token generated.');
    }

    public function usage(string $tokenValue)
    {
        $token = $this->tokens->findByValue($tokenValue);

        $activities = Activity::where('properties->token_value', $tokenValue)
            ->with('causer')
            ->latest()
            ->get();

        if (!$token && $activities->isEmpty()) {
            abort(404);
        }

        $teacherId = $token?->teacher_id
            ?? (int) $activities->first()?->properties->get('teacher_id');

        $backRoute = $teacherId
            ? route('admin.tokens.index', ['teacher_id' => $teacherId])
            : route('admin.tokens.index');

        return view('tokens.usage', [
            'tokenValue' => $tokenValue,
            'token'      => $token,
            'activities' => $activities,
            'layout'     => 'layouts.admin',
            'backRoute'  => $backRoute,
        ]);
    }

    public function revoke(int $id)
    {
        $token = $this->tokens->find($id);
        abort_if(is_null($token), 404);

        $this->tokens->revoke($token);

        return back()->with('success', 'Token revoked.');
    }
}
