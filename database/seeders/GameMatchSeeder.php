<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;
use App\Models\GameMatch;
use Carbon\Carbon;

class GameMatchSeeder extends Seeder
{
    public function run(): void
    {
        $games = Game::where('is_active', true)->get();
        
        if ($games->isEmpty()) {
            $this->command->warn('Aucun jeu actif trouvé. Exécutez d\'abord GameSeeder.');
            return;
        }

        $teamNames = [
            'PUBG Mobile' => [
                ['Team Alpha', 'Team Beta'],
                ['Squad Elite', 'Warriors Pro'],
                ['Champions League', 'Victory Squad'],
            ],
            'Garena Free Fire' => [
                ['Fire Squad', 'Ice Warriors'],
                ['Elite Players', 'Pro Gamers'],
                ['Champions', 'Legends'],
            ],
            'Mobile Legends: Bang Bang' => [
                ['Dragon Slayers', 'Phoenix Rising'],
                ['Shadow Warriors', 'Light Brigade'],
                ['Mythic Team', 'Epic Squad'],
            ],
            'Call of Duty: Mobile' => [
                ['Delta Force', 'Alpha Squad'],
                ['Tactical Team', 'Combat Elite'],
                ['Warriors', 'Soldiers'],
            ],
            'Clash Royale' => [
                ['Royal Guard', 'Crown Knights'],
                ['Elite Clan', 'Legendary Squad'],
                ['Champions', 'Masters'],
            ],
            'Brawl Stars' => [
                ['Star Warriors', 'Galaxy Squad'],
                ['Elite Brawlers', 'Pro Players'],
                ['Champions', 'Legends'],
            ],
            'EA SPORTS FC Mobile' => [
                ['FC Champions', 'Elite FC'],
                ['Victory FC', 'Legends FC'],
                ['Pro FC', 'Master FC'],
            ],
            'Clash of Clans' => [
                ['Clan Elite', 'Warriors Clan'],
                ['Champions Clan', 'Legends Clan'],
                ['Masters Clan', 'Pro Clan'],
            ],
            'Arena of Valor' => [
                ['Valor Warriors', 'Elite Valor'],
                ['Champions', 'Legends'],
                ['Pro Players', 'Masters'],
            ],
            'Honor of Kings' => [
                ['Kings Guard', 'Royal Squad'],
                ['Elite Kings', 'Champions'],
                ['Legends', 'Masters'],
            ],
            'Efootball' => [
                ['FC Elite', 'Champions FC'],
                ['Pro FC', 'Legends FC'],
                ['Masters FC', 'Victory FC'],
            ],
        ];

        foreach ($games as $game) {
            $gameTeams = $teamNames[$game->name] ?? [
                ['Team A', 'Team B'],
                ['Elite Squad', 'Pro Team'],
                ['Champions', 'Legends'],
            ];

            // Créer 3 matches par jeu
            for ($i = 0; $i < 3; $i++) {
                $matchDate = Carbon::now()->addDays($i + 1)->addHours(rand(10, 20));
                
                GameMatch::create([
                    'game_id' => $game->id,
                    'team1_name' => $gameTeams[$i][0],
                    'team2_name' => $gameTeams[$i][1],
                    'description' => "Match de {$game->name} entre {$gameTeams[$i][0]} et {$gameTeams[$i][1]}",
                    'match_date' => $matchDate,
                    'status' => 'upcoming',
                    'team1_odds' => 1.00, // Victoire team1
                    'draw_odds' => 0.50,  // Match nul
                    'team2_odds' => 1.00, // Victoire team2
                    'is_active' => true,
                ]);
            }
        }
    }
}
