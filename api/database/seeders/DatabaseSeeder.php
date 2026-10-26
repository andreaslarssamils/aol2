<?php

namespace Database\Seeders;

use App\Enums\SiteStatus;
use App\Models\Kommun;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LanSeeder::class,
            KommunSeeder::class,
            RoleSeeder::class,
            CategorySeeder::class,
        ]);

        // Första sajten: Allt om Ludvika.
        $ludvika = Kommun::where('kod', '2085')->firstOrFail();

        $site = Site::firstOrCreate(
            ['slug' => 'ludvika'],
            ['namn' => 'Allt om Ludvika', 'primary_kommun_id' => $ludvika->id, 'status' => SiteStatus::Active],
        );
        $site->kommuner()->syncWithoutDetaching([$ludvika->id => ['sort_order' => 0]]);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        )->syncRoles([User::ROLE_ADMIN]);
    }
}
