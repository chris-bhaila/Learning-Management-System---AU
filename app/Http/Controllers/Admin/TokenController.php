<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTokenRequest;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\TokenRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;

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

        return view('admin.tokens.index', [
            'teachers'     => $teachers,
            'teacher'      => $teacher,
            'classTokens'  => $teacher ? $this->tokens->getClassTokensByTeacher($teacher->id) : collect(),
            'courseTokens' => $teacher ? $this->tokens->getCourseTokensByTeacher($teacher->id) : collect(),
            'courses'      => $teacher ? $this->courses->getByTeacher($teacher->id) : collect(),
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

    public function destroy(int $id)
    {
        $token = $this->tokens->find($id);
        $this->tokens->delete($token);

        return back()->with('success', 'Token revoked.');
    }
}
