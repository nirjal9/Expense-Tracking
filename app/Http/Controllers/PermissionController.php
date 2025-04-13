<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{

    public function index(Request $request)
    {
        $permissions = Permission::get()->groupBy('group');
        if ($request->role_id) {
            $roles = Role::where('id', $request->role_id)->get();
        } else {
            $roles = Role::where('name', '!=', 'admin')->get();
        }
        return view('admin.permissions', compact('permissions', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        DB::beginTransaction();
        try {
            foreach ($request->permissions as $roleId => $permissionIds) {
                $role = Role::findOrFail($roleId);

                if ($role->name === 'admin') {
                    continue;
                }

                $role->permissions()->sync($permissionIds);
            }
            DB::commit();
            return redirect()->back()->with('success', 'Permissions updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Something went wrong while updating permissions.');
        }
    }

    public function destroy(Role $role, Permission $permission)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        if ($role->name === 'admin') {
            return redirect()->back()->with('error', 'Cannot modify admin role permissions.');
        }

        $role->permissions()->detach($permission->id);
        return redirect()->back()->with('success', 'Permission removed successfully');
    }
}
