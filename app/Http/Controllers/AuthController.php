<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string'
            ]);

            // Check for demo login
            if ($request->username === 'demo' && $request->password === '5555') {
                $user = [
                    'id' => 1,
                    'username' => 'demo',
                    'email' => 'demo@example.com',
                    'role' => 'admin'
                ];

                $secret = env('JWT_SECRET');
                $payload = [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'exp' => time() + (24 * 60 * 60) // 24 hours
                ];

                $token = JWT::encode($payload, $secret, 'HS256');

                return response()->json([
                    'message' => 'success',
                    'token' => $token,
                    'user' => $user
                ]);
            }

            return response()->json(['error' => 'Invalid credentials'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}