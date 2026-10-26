<?php

namespace Database\Factories;

use App\Models\Kommun;
use App\Models\Lan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Kommun>
 */
class KommunFactory extends Factory
{
    public function definition(): array
    {
        $namn = fake()->unique()->city();

        return [
            'lan_id' => Lan::factory(),
            'namn' => $namn,
            'kod' => str_pad((string) fake()->unique()->numberBetween(100, 9999), 4, '0', STR_PAD_LEFT),
            'slug' => Str::slug($namn),
            'lat' => fake()->latitude(55.3, 69.0),
            'lng' => fake()->longitude(11.0, 24.0),
        ];
    }
}
