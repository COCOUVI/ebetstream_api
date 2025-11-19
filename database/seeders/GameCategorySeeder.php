<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GameCategory;

class GameCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Jeux Mobiles',
                'slug' => 'jeux-mobiles',
                'description' => 'Les meilleurs jeux mobiles pour les paris esport',
                'position' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            GameCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
