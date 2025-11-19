<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'username'   => 'required|string|max:50|unique:users,username',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6|confirmed', // password_confirmation doit être présent
            'promo_code' => 'nullable|string|max:50',
        ]);

        // Création de l'utilisateur
        $user = User::create([
            'username'   => $validated['username'],
            'email'      => $validated['email'],
            'password'   => Hash::make($validated['password']),
            'promo_code' => $validated['promo_code'] ?? null,
        ]);

        // Création automatique du wallet en USD (si n'existe pas déjà)
        Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0.00,
                'locked_balance' => 0.00,
                'currency' => 'USD',
            ]
        );

        // Création du token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user'    => $user,
            'token'   => $token,
        ]);
    }
}
