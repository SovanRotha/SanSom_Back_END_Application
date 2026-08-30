<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\User\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Get all users
    public function index()
    {
        $users = User::with('role')->get();

        return response()->json([
            'message' => 'Users retrieved successfully',
            'users' => $users
        ]);
    }


    // Get one user
    public function show($id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'message' => 'User retrieved successfully',
            'user' => $user
        ]);
    }


    // Create user
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',

            
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email'
            ],

            'password' => 'required|string|min:8',

            'phone' => 'nullable|string|max:30',

            'profile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'currency' => [
                'required',
                Rule::in(['USD', 'KHR'])
            ],

            'status' => 'nullable|string',
        ]);

        $profilePath = null;

        if ($request->hasFile('profile')) {
            $profilePath = $request->file('profile')
                ->store('profiles', 'public');
        }

        $defaultRole = Role::where('role', 'user')->firstOrFail();

        $user = User::create([
            'name' => $validated['name'],
            'role_id' => $defaultRole->id,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'profile' => $profilePath ?? null,
            'currency' => $validated['currency'],
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }


    // Update user
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id)
            ],

            'phone' => 'nullable|string|max:30',

            'profile' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'currency' => [
                'sometimes',
                'required',
                Rule::in(['USD', 'KHR'])
            ],

            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive', 'suspended'])
            ],
        ]);

        if ($request->hasFile('profile')) {

            // Delete old image
            if ($user->profile) {
                Storage::disk('public')->delete($user->profile);
            }

            // Store new image
            $profilePath = $request->file('profile')
                ->store('profiles', 'public');

            $validated['profile'] = $profilePath;
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('role')
        ]);
    }


    // Delete user
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }
}
