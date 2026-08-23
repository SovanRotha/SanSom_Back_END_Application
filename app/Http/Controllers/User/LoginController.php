<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // Validate login data
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Find user
        $user = User::with('role')
            ->where('email', $validated['email'])
            ->first();

        // Check user
        if (!$user) {
            return response()->json([
                'message' => 'Invalid email or password'
            ], 401);
        }

        // Check password
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password'
            ], 401);
        }

        // Check account status
        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is not active'
            ], 403);
        }

        // Create Sanctum token
        $token = $user->createToken('sanSom-token')->plainTextToken;

        // Update last login
        $user->update([
            'last_login_at' => now(),
        ]);

        return response()->json([
            'message' => 'Login successful',

            'token' => $token,

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile' => $user->profile,
                'currency' => $user->currency,
                'status' => $user->status,
                'role' => $user->role->name,
                'last_login_at' => $user->last_login_at,
            ],
        ]);
    }
}