<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;

class UserObserver
{
    public function created(User $user)
    {
        // Crée automatiquement un wallet en USD pour chaque nouvel utilisateur (si n'existe pas déjà)
        Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'locked_balance' => 0,
                'currency' => 'USD',
            ]
        );

        // Génère automatiquement un code promo unique pour chaque nouvel utilisateur s'il n'en a pas déjà un
        if (!$user->promo_code) {
            $promoCode = $this->generateUniquePromoCode($user);
            $user->promo_code = $promoCode;
            $user->saveQuietly(); // Utilise saveQuietly pour éviter les boucles infinies
        }
    }

    /**
     * Génère un code promo unique basé sur le username de l'utilisateur
     */
    private function generateUniquePromoCode(User $user): string
    {
        // Génère un code basé sur le username (majuscules) + un suffixe aléatoire
        $baseCode = strtoupper(Str::slug($user->username, ''));
        $suffix = Str::random(4);
        $promoCode = $baseCode . $suffix;

        // Vérifie que le code est unique
        while (User::where('promo_code', $promoCode)->exists()) {
            $suffix = Str::random(4);
            $promoCode = $baseCode . $suffix;
        }

        return $promoCode;
    }
}
