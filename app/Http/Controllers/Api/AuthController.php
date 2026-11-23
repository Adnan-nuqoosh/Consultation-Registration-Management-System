<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $credentials['email'])->where('is_active', true)->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)
            || ! in_array($user->role, ['admin', 'manager', 'agent'], true)) {
            return response()->json(['success' => false, 'message' => 'The supplied credentials are invalid.'], 401);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'consultation-admin')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Authenticated successfully.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->only(['id', 'name', 'email', 'role']),
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'data' => $request->user()->only(['id', 'name', 'email', 'role'])]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();
        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }
}
