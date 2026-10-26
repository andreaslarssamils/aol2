<?php

namespace Database\Factories;

use App\Models\Kommun;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kommun_id' => Kommun::factory(),
            'email' => fake()->unique()->safeEmail(),
            'consented_at' => now(),
            'confirmed_at' => null,
            'unsubscribed_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['confirmed_at' => now()]);
    }
}
