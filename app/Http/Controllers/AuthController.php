<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    # Register controller
    public function register(Request $request)
    {
        // Logic for user registration
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    # Login controller
    public function login(Request $request)
    {
        // Logic for user login
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (! auth()->attempt($request->only('email', 'password')))
        {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User logged in successfully',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    # Logout controller
    public function logout(Request $request)
    {
        // Logic for user logout
        $user = auth()->user();
        $user->tokens()->delete();

        return response()->json(['message' => 'User logged out successfully'], 200);
    }
}
