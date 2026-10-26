<?php

namespace Database\Factories;

use App\Models\BusinessLocation;
use App\Models\OpeningHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningHour>
 */
class OpeningHourFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_location_id' => BusinessLocation::factory(),
            'weekday' => fake()->numberBetween(0, 6),
            'opens' => '09:00',
            'closes' => '17:00',
            'closed' => false,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['opens' => null, 'closes' => null, 'closed' => true]);
    }
}
