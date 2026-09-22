<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'captcha_id' => 'required|string',
            'captcha_answer' => 'required|string',
        ]);

        $captchaKey = 'captcha_' . $request->captcha_id;
        $cachedHash = Cache::get($captchaKey);
        
        // Hapus cache CAPTCHA (sekali pakai)
        Cache::forget($captchaKey);

        if (!$cachedHash || !Hash::check(strtoupper(trim($request->captcha_answer)), $cachedHash)) {
            return response()->json([
                'message' => 'Username, password, atau kode keamanan tidak valid.'
            ], 401);
        }

        $username = strtolower(trim($request->username));
        $user = User::where('username', $username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Username, password, atau kode keamanan tidak valid.'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'user' => $user
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'data' => $request->user()
        ]);
    }
}
