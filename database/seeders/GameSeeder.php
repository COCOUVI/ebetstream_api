<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\GameCategory;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer la catégorie "Jeux Mobiles"
        $category = GameCategory::where('slug', 'jeux-mobiles')->first();
        
        if (!$category) {
            $this->command->warn('Catégorie "Jeux Mobiles" non trouvée. Exécutez d\'abord GameCategorySeeder.');
            return;
        }

        $games = [
            [
                'name' => 'PUBG Mobile',
                'slug' => 'pubg-mobile',
                'description' => 'Battle royale mobile populaire avec des millions de joueurs',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Garena Free Fire',
                'slug' => 'garena-free-fire',
                'description' => 'Battle royale rapide et intense',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Legends: Bang Bang',
                'slug' => 'mobile-legends-bang-bang',
                'description' => 'MOBA mobile compétitif avec des tournois internationaux',
                'position' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Call of Duty: Mobile',
                'slug' => 'call-of-duty-mobile',
                'description' => 'FPS mobile avec modes multijoueurs et battle royale',
                'position' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Clash Royale',
                'slug' => 'clash-royale',
                'description' => 'Jeu de stratégie en temps réel avec cartes',
                'position' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Brawl Stars',
                'slug' => 'brawl-stars',
                'description' => 'Combat multijoueur rapide et amusant',
                'position' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'EA SPORTS FC Mobile',
                'slug' => 'ea-sports-fc-mobile',
                'description' => 'Football mobile avec des modes compétitifs',
                'position' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Clash of Clans',
                'slug' => 'clash-of-clans',
                'description' => 'Stratégie de construction et de combat',
                'position' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Arena of Valor',
                'slug' => 'arena-of-valor',
                'description' => 'MOBA mobile avec des héros uniques',
                'position' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'Honor of Kings',
                'slug' => 'honor-of-kings',
                'description' => 'MOBA mobile très populaire en Asie',
                'position' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Efootball',
                'slug' => 'efootball',
                'description' => 'Simulation de football avec gameplay réaliste',
                'position' => 11,
                'is_active' => true,
            ],
        ];

        foreach ($games as $game) {
            Game::firstOrCreate(
                [
                    'game_category_id' => $category->id,
                    'slug' => $game['slug']
                ],
                array_merge($game, ['game_category_id' => $category->id])
            );
        }
    }
}
