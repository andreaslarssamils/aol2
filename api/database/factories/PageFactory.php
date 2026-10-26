<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $titel = fake()->unique()->words(2, true);

        return [
            'kommun_id' => null,
            'titel' => ['sv' => Str::ucfirst($titel), 'en' => Str::ucfirst($titel)],
            'body' => ['sv' => '<p>'.fake()->paragraph().'</p>', 'en' => '<p>'.fake()->paragraph().'</p>'],
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (Page $page) => $page->setSlug('sv', Str::slug($page->titel['sv'])));
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);
    }
}
