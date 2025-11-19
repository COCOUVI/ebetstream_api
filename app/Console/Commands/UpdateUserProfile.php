<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Profile;

class UpdateUserProfile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:update-profile {user_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user profile to meet certification requirements';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("Utilisateur {$userId} non trouvé");
            return 1;
        }

        $profile = Profile::firstOrCreate(
            ['user_id' => $user->id],
            ['username' => $user->username]
        );

        // Remplir tous les champs requis pour la certification
        $profile->full_name = $profile->full_name ?: 'John Doe';
        $profile->bio = $profile->bio ?: 'Gamer professionnel passionné par les compétitions esport. J\'aime relever des défis et participer à des tournois.';
        $profile->country = $profile->country ?: 'France';
        
        // Si pas de photo, on met un placeholder (l'utilisateur devra en ajouter une vraie)
        if (!$profile->profile_photo && !$profile->avatar) {
            // On peut créer un fichier placeholder ou laisser vide pour l'instant
            // Pour les tests, on va juste mettre une valeur
            $profile->profile_photo = 'profiles/placeholder.jpg';
        }

        $profile->save();

        $this->info("✓ Profil de l'utilisateur {$userId} mis à jour avec succès");
        $this->line("  - Nom complet: {$profile->full_name}");
        $this->line("  - Bio: " . substr($profile->bio, 0, 50) . "...");
        $this->line("  - Pays: {$profile->country}");
        $this->line("  - Photo: " . ($profile->profile_photo ?: 'Non définie'));

        return 0;
    }
}
