<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $namn = fake()->unique()->words(2, true);

        return [
            'parent_id' => null,
            'type' => CategoryType::Business,
            'namn' => ['sv' => Str::ucfirst($namn), 'en' => Str::ucfirst($namn)],
            'slug' => Str::slug($namn),
            'icon' => null,
            'sort_order' => 0,
        ];
    }

    public function event(): static
    {
        return $this->state(fn () => ['type' => CategoryType::Event]);
    }
}
