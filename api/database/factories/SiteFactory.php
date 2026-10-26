<?php

namespace Database\Factories;

use App\Enums\SiteStatus;
use App\Models\Kommun;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kommun_id' => Kommun::factory(),
            'namn' => fn (array $attributes) => 'Allt om '.Kommun::find($attributes['kommun_id'])->namn,
            'domain' => null,
            'status' => SiteStatus::ComingSoon,
            'settings' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => SiteStatus::Active]);
    }
}
