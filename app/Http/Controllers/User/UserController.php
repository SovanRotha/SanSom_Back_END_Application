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

    public function index(Request $request)
    {
        $search = $request->query('search');

        $users = User::select([
                'id',
                'name',
                'email',
                'role_id',
                'status',
                'created_at',
                'updated_at',
            ])
            ->with('role')
            // Aggregate total income from transactions
            ->withSum(['transactions as total_income' => function ($query) {
                $query->where('type', 'income')
                    ->where('status', 'completed');
            }], 'amount')
            // Aggregate total expense from transactions
            ->withSum(['transactions as total_expense' => function ($query) {
                $query->where('type', 'expense')
                    ->where('status', 'completed');
            }], 'amount')
            ->where('role_id', 2) // Normal users only
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        $users->each(function (User $user) {
            $user->total_income = (float) ($user->total_income ?? 0);
            $user->total_expense = (float) ($user->total_expense ?? 0);
        });

        return response()->json([
            'message' => 'Users retrieved successfully',
            'users' => $users,
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
            'users' => $user
        ]);
    }

    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }

        $user = User::with('role')->find($user->id);

        return response()->json([
            'message' => 'Authenticated user retrieved successfully',
            'users' => $user
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
                'nullable',
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
            'users' => $user,
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

        $user = User::with('role')->find($user->id);

        return response()->json([
            'message' => 'User updated successfully',
            'users' => $user
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
