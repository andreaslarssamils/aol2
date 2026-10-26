<?php

namespace Database\Factories;

use App\Enums\SiteStatus;
use App\Models\Kommun;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        $ort = fake()->unique()->city();

        return [
            'namn' => 'Allt om '.$ort,
            'slug' => Str::slug($ort),
            'domain' => null,
            'primary_kommun_id' => Kommun::factory(),
            'status' => SiteStatus::ComingSoon,
            'settings' => null,
        ];
    }

    /** Primärkommunen visas alltid på sajten. */
    public function configure(): static
    {
        return $this->afterCreating(function (Site $site) {
            if ($site->primary_kommun_id !== null) {
                $site->kommuner()->syncWithoutDetaching([$site->primary_kommun_id => ['sort_order' => 0]]);
            }
        });
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => SiteStatus::Active]);
    }
}
