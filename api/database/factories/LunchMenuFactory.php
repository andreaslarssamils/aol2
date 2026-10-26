<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\BusinessLocation;
use App\Models\LunchMenu;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LunchMenu>
 */
class LunchMenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_location_id' => BusinessLocation::factory(),
            'year' => (int) now()->format('o'),
            'week' => (int) now()->format('W'),
            'menu' => [
                'mon' => [fake()->sentence(3)],
                'tue' => [fake()->sentence(3)],
                'wed' => [fake()->sentence(3)],
                'thu' => [fake()->sentence(3)],
                'fri' => [fake()->sentence(3)],
            ],
            'status' => ContentStatus::Pending,
        ];
    }
}
