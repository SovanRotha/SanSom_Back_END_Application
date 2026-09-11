<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleSignInController extends Controller
{
    /**
     * Register with Google
     */
    public function registerWithGoogle(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        // Verify Google ID Token
        $payload = $this->verifyGoogleToken($request->id_token);

        if (!$payload) {
            return response()->json([
                'message' => 'Invalid Google ID token.',
            ], 401);
        }

        $googleId = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'] ?? 'Google User';
        $avatar = $payload['picture'] ?? null;

        // Check if user already exists
        $existingUser = User::where('email', $email)
            ->orWhere('google_id', $googleId)
            ->first();

        if ($existingUser) {
            return response()->json([
                'message' => 'This Google account is already registered. Please login instead.',
            ], 409);
        }

        // Create new user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'google_id' => $googleId,
            'avatar' => $avatar,
            'password' => Hash::make(Str::random(32)),
            'role_id' => 2,
        ]);

        // Create Sanctum token
        $token = $user->createToken('google-register')->plainTextToken;

        return response()->json([
            'message' => 'Google registration successful.',
            'token' => $token,
            'user' => $user,
        ], 201);
    }


    /**
     * Login with Google
     */
    public function loginWithGoogle(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        // Verify Google ID Token
        $payload = $this->verifyGoogleToken($request->id_token);

        if (!$payload) {
            return response()->json([
                'message' => 'Invalid Google ID token.',
            ], 401);
        }

        $googleId = $payload['sub'];
        $email = $payload['email'];

        // Find existing user
        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        // User does not exist
        if (!$user) {
            return response()->json([
                'message' => 'Account not found. Please register with Google first.',
            ], 404);
        }

        // Create Sanctum token
        $token = $user->createToken('google-login')->plainTextToken;

        return response()->json([
            'message' => 'Google login successful.',
            'token' => $token,
            'user' => $user,
        ], 200);
    }


    /**
     * Verify Google ID Token
     */
    private function verifyGoogleToken(string $idToken)
    {
        $client = new GoogleClient([
            'client_id' => env('GOOGLE_CLIENT_ID'),
        ]);

        return $client->verifyIdToken($idToken);
    }
}