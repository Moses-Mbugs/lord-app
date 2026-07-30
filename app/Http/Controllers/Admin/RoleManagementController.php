<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    /**
     * Roles that can never be deleted, since removing them would lock
     * everyone out of this page (admin) or break existing route gating
     * (finance-admin is referenced throughout routes/web.php).
     */
    private const PROTECTED_ROLES = ['admin', 'finance-admin'];

    public function index()
    {
        $users = User::orderBy('name')->with('roles')->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.roles.index', compact('users', 'roles'));
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        Role::create([
            'name' => $request->input('name'),
            'guard_name' => 'web',
        ]);

        return redirect()->route('admin.roles.index')->with('success', "Role '{$request->input('name')}' created.");
    }

    public function destroyRole(Role $role)
    {
        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            return redirect()->route('admin.roles.index')->with('error', "The '{$role->name}' role can't be deleted.");
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' deleted.");
    }

    public function syncRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->syncRoles($request->input('roles', []));

        return redirect()->route('admin.roles.index')->with('success', "Roles updated for {$user->name}.");
    }
}
