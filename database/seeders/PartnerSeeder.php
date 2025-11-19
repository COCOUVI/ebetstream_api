<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Partner;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            [
                'name' => 'Epic Games',
                'specialty' => 'Moteurs de jeu et développement AAA',
                'website' => 'https://www.epicgames.com',
                'country' => 'États-Unis',
                'bio' => 'Créateur d\'Unreal Engine et de jeux emblématiques comme Fortnite. Leader mondial dans le développement de moteurs de jeu et d\'expériences interactives.',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Unity Technologies',
                'specialty' => 'Moteur de jeu multiplateforme',
                'website' => 'https://unity.com',
                'country' => 'États-Unis',
                'bio' => 'Unity est l\'un des moteurs de jeu les plus utilisés au monde, permettant de créer des jeux pour toutes les plateformes.',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Riot Games',
                'specialty' => 'Jeux compétitifs et esports',
                'website' => 'https://www.riotgames.com',
                'country' => 'États-Unis',
                'bio' => 'Développeur de League of Legends et Valorant, pionnier dans l\'esport et les jeux compétitifs en ligne.',
                'position' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Valve Corporation',
                'specialty' => 'Plateforme de distribution et jeux PC',
                'website' => 'https://www.valvesoftware.com',
                'country' => 'États-Unis',
                'bio' => 'Créateur de Steam, la plus grande plateforme de distribution de jeux PC, et développeur de Half-Life, Counter-Strike, et Dota 2.',
                'position' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Blizzard Entertainment',
                'specialty' => 'Jeux de rôle et stratégie',
                'website' => 'https://www.blizzard.com',
                'country' => 'États-Unis',
                'bio' => 'Développeur légendaire de World of Warcraft, Overwatch, Diablo et StarCraft. Maître dans la création d\'univers immersifs.',
                'position' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Ubisoft',
                'specialty' => 'Jeux d\'action-aventure et open-world',
                'website' => 'https://www.ubisoft.com',
                'country' => 'France',
                'bio' => 'Studio français renommé pour Assassin\'s Creed, Far Cry, et Watch Dogs. Expert en création de mondes ouverts immersifs.',
                'position' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Electronic Arts (EA)',
                'specialty' => 'Sports et jeux de simulation',
                'website' => 'https://www.ea.com',
                'country' => 'États-Unis',
                'bio' => 'Leader dans les jeux de sport avec FIFA, Madden NFL, et développeur de franchises comme Battlefield et The Sims.',
                'position' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'CD Projekt RED',
                'specialty' => 'RPG narratifs et open-world',
                'website' => 'https://www.cdprojekt.com',
                'country' => 'Pologne',
                'bio' => 'Créateur de The Witcher et Cyberpunk 2077. Reconnu pour ses récits immersifs et ses mondes ouverts détaillés.',
                'position' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Nintendo',
                'specialty' => 'Jeux familiaux et consoles',
                'website' => 'https://www.nintendo.com',
                'country' => 'Japon',
                'bio' => 'Légende du jeu vidéo avec des franchises iconiques comme Mario, Zelda, et Pokémon. Innovateur dans le hardware et le gameplay.',
                'position' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'Square Enix',
                'specialty' => 'RPG japonais et jeux narratifs',
                'website' => 'https://www.square-enix.com',
                'country' => 'Japon',
                'bio' => 'Développeur de Final Fantasy, Kingdom Hearts, et Tomb Raider. Maître dans la narration et les RPG épiques.',
                'position' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($partners as $partner) {
            Partner::create($partner);
        }
    }
}
