<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Seeder;

/** Grunduppsättning kategorier. Utöka fritt – slug + type är nyckeln. */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            CategoryType::Business->value => [
                ['slug' => 'restaurang-cafe', 'sv' => 'Restaurang & café', 'en' => 'Restaurants & cafés'],
                ['slug' => 'butik', 'sv' => 'Butik', 'en' => 'Shops'],
                ['slug' => 'hantverk-service', 'sv' => 'Hantverk & service', 'en' => 'Trades & services'],
                ['slug' => 'halsa-skonhet', 'sv' => 'Hälsa & skönhet', 'en' => 'Health & beauty'],
                ['slug' => 'boende', 'sv' => 'Boende', 'en' => 'Accommodation'],
                ['slug' => 'fritid-upplevelser', 'sv' => 'Fritid & upplevelser', 'en' => 'Leisure & experiences'],
                ['slug' => 'forening', 'sv' => 'Förening', 'en' => 'Associations'],
            ],
            CategoryType::Event->value => [
                ['slug' => 'musik', 'sv' => 'Musik', 'en' => 'Music'],
                ['slug' => 'kultur', 'sv' => 'Kultur', 'en' => 'Culture'],
                ['slug' => 'sport-motion', 'sv' => 'Sport & motion', 'en' => 'Sports'],
                ['slug' => 'marknad-mat', 'sv' => 'Marknad & mat', 'en' => 'Markets & food'],
                ['slug' => 'barn-familj', 'sv' => 'Barn & familj', 'en' => 'Kids & family'],
            ],
        ];

        foreach ($categories as $type => $rows) {
            foreach ($rows as $index => $row) {
                Category::updateOrCreate(
                    ['slug' => $row['slug'], 'type' => $type],
                    ['namn' => ['sv' => $row['sv'], 'en' => $row['en']], 'sort_order' => $index],
                );
            }
        }
    }
}
