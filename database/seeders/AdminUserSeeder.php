<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur admin par défaut
        $admin = User::firstOrCreate(
            ['email' => 'admin@ebetstream.com'],
            [
                'username' => 'admin',
                'email' => 'admin@ebetstream.com',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Créer le profil si n'existe pas
        if (!$admin->profile) {
            Profile::create([
                'user_id' => $admin->id,
                'username' => 'admin',
                'full_name' => 'Administrateur',
            ]);
        }

        // Créer le wallet si n'existe pas
        Wallet::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'balance' => 0,
                'locked_balance' => 0,
                'currency' => 'USD',
            ]
        );
    }
}
