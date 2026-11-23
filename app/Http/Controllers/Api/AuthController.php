<?php

namespace App\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use App\\Models\\User;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->where('email', $credentials['email'])
            ->where('is_active', true)
            ->first();

        // This dashboard has one simple account level: active administrators.
        if (! $user || $user->role !== 'admin' || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The supplied credentials are invalid.',
            ], 401);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'website-submissions-dashboard')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Authenticated successfully.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->only(['id', 'name', 'email']),
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->only(['id', 'name', 'email']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
