<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private RoleRepositoryInterface $roles,
    ) {}

    public function index(Request $request)
    {
        $role   = in_array($request->get('role'), ['admin', 'teacher', 'student'])
                    ? $request->get('role')
                    : 'admin';
        $sort   = in_array($request->get('sort'), ['recent', 'oldest', 'az'])
                    ? $request->get('sort')
                    : 'recent';
        $search = $request->get('search');

        return view('admin.users.index', [
            'users'      => $this->users->getFilteredUsers($role, $sort, $search),
            'roleCounts' => $this->users->getRoleCounts(),
            'roles'      => $this->roles->all(),
        ]);
    }

    public function update(UpdateUserRoleRequest $request, int $id)
    {
        $user = $this->users->find($id);
        $role = $this->roles->findByName($request->validated('role'));

        $this->users->updateRole($user, $role->id);

        return back()->with('success', 'User role updated.');
    }

    public function destroy(int $id)
    {
        $user = $this->users->find($id);
        $this->users->delete($user);

        return back()->with('success', 'User deleted.');
    }
}