<?php

namespace Database\Factories;

use App\Models\Lan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lan>
 */
class LanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'namn' => fake()->unique()->city().'s län',
            'kod' => str_pad((string) fake()->unique()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT),
        ];
    }
}
