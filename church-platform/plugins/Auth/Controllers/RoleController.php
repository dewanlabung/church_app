<?php

namespace Plugins\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(Role::with('permissions')->get());
    }

    public function permissions()
    {
        return response()->json(Permission::all()->groupBy(fn($p) => explode('.', $p->name)[0]));
    }

    public function updatePermissions(Request $request, string $roleName)
    {
        $role = Role::findByName($roleName);
        $permissions = $request->validate([
            'permissions'   => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ])['permissions'];

        $role->syncPermissions($permissions);

        return response()->json($role->fresh(['permissions']));
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|unique:roles,name',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name']]);
        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function destroy(string $roleName)
    {
        $protected = ['super_admin', 'church_admin', 'counsellor', 'musician', 'general_user'];
        if (in_array($roleName, $protected)) {
            return response()->json(['message' => 'Cannot delete built-in roles.'], 422);
        }

        $role = Role::findByName($roleName);
        $role->delete();

        return response()->json(['message' => 'Role deleted.']);
    }
}
