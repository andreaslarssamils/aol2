<?php

namespace Database\Factories;

use App\Models\BusinessLocation;
use App\Models\OpeningHourException;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningHourException>
 */
class OpeningHourExceptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_location_id' => BusinessLocation::factory(),
            'date' => fake()->dateTimeBetween('+1 day', '+3 months')->format('Y-m-d'),
            'opens' => null,
            'closes' => null,
            'closed' => true,
            'note' => 'Semesterstängt',
        ];
    }

    public function open(string $opens = '10:00', string $closes = '14:00'): static
    {
        return $this->state(fn () => ['opens' => $opens, 'closes' => $closes, 'closed' => false]);
    }
}
