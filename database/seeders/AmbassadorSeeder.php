<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ambassador;

class AmbassadorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ambassadors = [
            [
                'name' => 'Alexandre Dubois',
                'username' => 'AlexProGamer',
                'avatar' => null,
                'score' => 1250,
                'country' => 'France',
                'bio' => 'Champion esportif professionnel avec plus de 5 ans d\'expérience. Spécialisé dans les jeux de combat et les tournois compétitifs.',
                'position' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Sophie Martin',
                'username' => 'SophieStream',
                'avatar' => null,
                'score' => 1190,
                'country' => 'Belgique',
                'bio' => 'Streamer professionnelle et créatrice de contenu gaming. Passionnée par les jeux de stratégie et les défis communautaires.',
                'position' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Mohamed Benali',
                'username' => 'MohamedElite',
                'avatar' => null,
                'score' => 1120,
                'country' => 'Maroc',
                'bio' => 'Joueur compétitif reconnu dans les tournois internationaux. Expert en jeux de tir et stratégie en équipe.',
                'position' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Emma Johnson',
                'username' => 'EmmaGaming',
                'avatar' => null,
                'score' => 1080,
                'country' => 'Canada',
                'bio' => 'Influenceuse gaming et coach esport. Aide les nouveaux joueurs à progresser et à atteindre leurs objectifs.',
                'position' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Lucas Silva',
                'username' => 'LucasPro',
                'avatar' => null,
                'score' => 1025,
                'country' => 'Brésil',
                'bio' => 'Joueur professionnel de football virtuel. Champion régional et participant aux compétitions mondiales.',
                'position' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Yuki Tanaka',
                'username' => 'YukiMaster',
                'avatar' => null,
                'score' => 980,
                'country' => 'Japon',
                'bio' => 'Spécialiste des jeux de combat et des tournois asiatiques. Connu pour son style de jeu technique et précis.',
                'position' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Maria Rodriguez',
                'username' => 'MariaChampion',
                'avatar' => null,
                'score' => 950,
                'country' => 'Espagne',
                'bio' => 'Streamer et compétitrice esport. Créatrice de contenu éducatif sur les stratégies de jeu avancées.',
                'position' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'David Müller',
                'username' => 'DavidElite',
                'avatar' => null,
                'score' => 920,
                'country' => 'Allemagne',
                'bio' => 'Joueur professionnel et analyste esport. Expert en métagame et stratégies compétitives.',
                'position' => 8,
                'is_active' => true,
            ],
            [
                'name' => 'Amélie Rousseau',
                'username' => 'AmelieGaming',
                'avatar' => null,
                'score' => 890,
                'country' => 'France',
                'bio' => 'Influenceuse gaming et organisatrice de tournois. Passionnée par la création de communautés de joueurs.',
                'position' => 9,
                'is_active' => true,
            ],
            [
                'name' => 'James Wilson',
                'username' => 'JamesPro',
                'avatar' => null,
                'score' => 860,
                'country' => 'Royaume-Uni',
                'bio' => 'Joueur compétitif et commentateur esport. Spécialisé dans les jeux de stratégie en temps réel.',
                'position' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($ambassadors as $ambassador) {
            Ambassador::create($ambassador);
        }
    }
}
