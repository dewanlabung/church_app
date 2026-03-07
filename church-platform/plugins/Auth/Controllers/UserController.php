<?php

namespace Plugins\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles')
            ->when($request->input('search'), fn($q, $s) =>
                $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")
            )
            ->when($request->input('role'), fn($q, $r) =>
                $q->role($r)
            )
            ->when($request->input('user_type'), fn($q, $t) =>
                $q->where('user_type', $t)
            );

        return response()->json($query->paginate(20));
    }

    public function show(int $id)
    {
        return response()->json(User::with('roles', 'permissions')->findOrFail($id));
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'email'     => 'sometimes|email|unique:users,email,' . $id,
            'user_type' => 'sometimes|in:super_admin,church_admin,counsellor,musician,general_user',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        if ($request->has('roles')) {
            $user->syncRoles($request->input('roles', []));
        }

        return response()->json($user->fresh(['roles']));
    }

    public function destroy(int $id)
    {
        $user = User::findOrFail($id);
        // Prevent deleting last super admin
        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return response()->json(['message' => 'Cannot delete the only super admin.'], 422);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted.']);
    }

    public function impersonate(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $token = $user->createToken('impersonate')->plainTextToken;
        return response()->json(['token' => $token]);
    }

    public function exportCsv(Request $request)
    {
        $users = User::with('roles')
            ->select('id', 'name', 'email', 'user_type', 'created_at')
            ->get();

        $csv = "id,name,email,user_type,roles,created_at\n";
        foreach ($users as $u) {
            $roles = $u->roles->pluck('name')->implode('|');
            $csv .= implode(',', [$u->id, '"'.$u->name.'"', $u->email, $u->user_type, $roles, $u->created_at])."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users-export.csv"',
        ]);
    }
}
