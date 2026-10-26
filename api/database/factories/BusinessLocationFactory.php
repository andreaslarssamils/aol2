<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Kommun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessLocation>
 */
class BusinessLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'kommun_id' => Kommun::factory(),
            'namn' => null,
            'telefon' => fake()->phoneNumber(),
            'adress' => fake()->streetAddress(),
            'postnummer' => fake()->postcode(),
            'ort' => fake()->city(),
            'lat' => fake()->latitude(55.3, 69.0),
            'lng' => fake()->longitude(11.0, 24.0),
            'is_primary' => true,
        ];
    }

    /** Varje lokalisering får en svensk slug direkt. */
    public function configure(): static
    {
        return $this->afterCreating(fn (BusinessLocation $location) => $location->setSlug('sv', fake()->unique()->slug(3)));
    }

    public function secondary(): static
    {
        return $this->state(fn () => ['is_primary' => false]);
    }
}
