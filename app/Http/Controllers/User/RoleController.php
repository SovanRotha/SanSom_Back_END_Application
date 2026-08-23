<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    // Get all roles
    public function index()
    {
        $roles = Role::all();

        return response()->json([
            'message' => 'Roles retrieved successfully',
            'roles' => $roles
        ]);
    }

    // Get one role
    public function show($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Role retrieved successfully',
            'role' => $role
        ]);
    }

    // Create role
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|string|max:50|unique:roles,role',
        ]);

        $role = Role::create([
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role
        ], 201);
    }

    // Update role
    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role not found'
            ], 404);
        }

        $validated = $request->validate([
            'role' => 'required|string|max:50|unique:roles,role,' . $id,
        ]);

        $role->update([
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role
        ]);
    }

    // Delete role
    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role not found'
            ], 404);
        }

        // Don't delete a role that is being used
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this role because it is assigned to users.'
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully'
        ]);
    }
}