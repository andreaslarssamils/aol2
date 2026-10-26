<?php

namespace Database\Factories;

use App\Enums\BusinessKind;
use App\Enums\ContentStatus;
use App\Enums\ManagedBy;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kind' => BusinessKind::Company,
            'managed_by' => ManagedBy::Editorial,
            'auto_approve' => false,
            'namn' => fake()->company(),
            'beskrivning' => ['sv' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'epost' => fake()->companyEmail(),
            'webbplats' => fake()->url(),
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function association(): static
    {
        return $this->state(fn () => ['kind' => BusinessKind::Association]);
    }
}
