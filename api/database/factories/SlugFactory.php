<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Slug;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Slug>
 *
 * Används normalt via $model->setSlug(); den här finns för Slug::factory()->for($model, 'slugable').
 */
class SlugFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slugable_type' => (new Article)->getMorphClass(),
            'slugable_id' => Article::factory(),
            'kommun_id' => null,
            'locale' => 'sv',
            'slug' => fake()->unique()->slug(3),
            'is_canonical' => true,
        ];
    }
}
