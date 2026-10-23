<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeRoleRequest;
use App\Models\User;
use App\Services\RoleManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RoleController extends Controller
{
    /**
     * Menampilkan daftar user untuk pengelolaan role (khusus admin).
     */
    public function index(): View
    {
        return view('admin.roles.index', [
            'users' => User::orderBy('username')->get(),
        ]);
    }

    public function update(
        ChangeRoleRequest $request,
        User $user,
        RoleManagementService $service
    ): RedirectResponse {
        $data = $request->validated();

        $service->changeRole($request->user(), $user, $data['role']);

        return back()->with('status', 'Role diperbarui.');
    }
}
