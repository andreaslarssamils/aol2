<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\Kommun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $titel = fake()->unique()->sentence(5);

        return [
            'kommun_id' => Kommun::factory(),
            'author_id' => User::factory(),
            'titel' => ['sv' => $titel, 'en' => $titel],
            'slug' => Str::slug($titel),
            'ingress' => ['sv' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'body' => ['sv' => '<p>'.fake()->paragraphs(3, true).'</p>', 'en' => '<p>'.fake()->paragraphs(3, true).'</p>'],
            'featured' => false,
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
}
