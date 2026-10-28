<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Kommun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessLocation>
 *
 * Publicerad som standard, till skillnad från övrigt innehåll – nästan alla tester vill ha en
 * synlig lokalisering. Använd draft() för godkännandeflödet.
 */
class BusinessLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'kommun_id' => Kommun::factory(),
            'namn' => null,
            // Formatet "0240-123 45 67" klarar Filaments tel()-regel; fakerns phoneNumber() gör det inte alltid.
            'telefon' => '0'.fake()->numberBetween(10, 99).'-'.fake()->numerify('### ## ##'),
            'adress' => fake()->streetAddress(),
            'postnummer' => fake()->postcode(),
            'ort' => fake()->city(),
            'lat' => fake()->latitude(55.3, 69.0),
            'lng' => fake()->longitude(11.0, 24.0),
            'is_primary' => true,
            'status' => ContentStatus::Published,
            'published_at' => now(),
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

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ]);
    }
}
