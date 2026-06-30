<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;

class UserController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
        private CourseRepositoryInterface $courses,
    ) {}

    public function index(Request $request)
    {
        $role   = in_array($request->get('role'), ['admin', 'teacher', 'student'])
                    ? $request->get('role')
                    : 'admin';
        $sort   = in_array($request->get('sort'), ['recent', 'oldest', 'az'])
                    ? $request->get('sort')
                    : 'recent';
        $status = in_array($request->get('status'), ['active', 'inactive'])
                    ? $request->get('status')
                    : null;
        $search = $request->get('search');

        $data = [
            'users'          => $this->users->getFilteredUsers($role, $sort, $search, $status),
            'roleCounts'     => $this->users->getRoleCounts(),
            'roles'          => $this->roles->all(),
            'currentRole'    => $role,
            'currentSort'    => $sort,
            'currentStatus'  => $status,
            'currentSearch'  => $search ?? '',
        ];

        if ($request->ajax()) {
            return view('admin.users._table', $data);
        }

        return view('admin.users.index', $data);
    }

    public function show(int $id)
    {
        $user = $this->users->find($id);
        abort_if(is_null($user), 404);

        if ($user->isTeacher()) {
            $user->load(['courses' => fn($q) => $q->withCount('students')->with('units')]);
        } elseif ($user->isStudent()) {
            $user->setRelation('enrolledTeachers', $this->users->getTeachersForStudent($user->id));
        }

        return view('admin.users.show', [
            'subject' => $user,
        ]);
    }

    public function showStudentClass(int $userId, int $teacherId)
    {
        $student = $this->users->getStudentWithTeacherPivot($userId, $teacherId);
        abort_if(is_null($student), 404);

        $teacher = $this->users->find($teacherId);
        abort_if(is_null($teacher), 404);

        return view('admin.users.student-class', [
            'student' => $student,
            'teacher' => $teacher,
            'courses' => $this->courses->getStudentCoursesForTeacher($userId, $teacherId),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $role = $this->roles->findByName($request->validated('role'));

        $this->users->create([
            'name'      => $request->validated('name'),
            'email'     => $request->validated('email'),
            'role_id'   => $role->id,
            'password'  => $request->validated('password'), // hashed by User model cast
            'is_active' => true,
        ]);

        return back()->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = $this->users->find($id);

        $updates = [
            'name'      => $request->validated('name'),
            'is_active' => $request->validated('is_active'),
        ];

        // Admin role is locked — never allow role changes on an admin account.
        if (!$user->isAdmin()) {
            $updates['role_id'] = $this->roles->findByName($request->validated('role'))->id;
        }

        $this->users->update($user, $updates);

        // Avatar — handled in same form submission as the profile update.
        if ($request->hasFile('avatar')) {
            $path = $this->resizeAndStore($request->file('avatar'));
            $this->users->updateAvatar($user->fresh(), $path);
        } elseif ($request->boolean('remove_avatar')) {
            $this->users->removeAvatar($user->fresh());
        }

        return back()->with('success', 'User updated.');
    }

    public function destroy(int $id)
    {
        $user = $this->users->find($id);
        $this->users->delete($user);

        return back()->with('success', 'User deleted.');
    }

    private function resizeAndStore(\Illuminate\Http\UploadedFile $file): string
    {
        $manager  = new ImageManager(new Driver());
        $image    = $manager->decode($file->getRealPath());
        $image->cover(256, 256);
        $encoded  = $image->encode(new WebpEncoder(90));

        $path = 'avatars/' . Str::uuid() . '.webp';
        Storage::disk('public')->put($path, (string) $encoded);

        return $path;
    }
}
