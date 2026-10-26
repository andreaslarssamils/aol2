<?php

namespace Database\Seeders;

use App\Models\Lan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/** Sveriges 21 län från SCB (database/data/lan.json). Idempotent via kod. */
class LanSeeder extends Seeder
{
    public function run(): void
    {
        $rows = File::json(database_path('data/lan.json'));

        Lan::upsert($rows, uniqueBy: ['kod'], update: ['namn']);
    }
}
