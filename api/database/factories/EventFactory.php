<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+2 months');

        return [
            'kommun_id' => Kommun::factory(),
            'business_location_id' => null,
            'parent_event_id' => null,
            'recurrence_rule' => null,
            'created_by' => User::factory(),
            'namn' => fake()->unique()->sentence(3),
            'beskrivning' => ['sv' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'all_day' => false,
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+2 hours'),
            'venue_name' => fake()->company(),
            'adress' => fake()->streetAddress(),
            'lat' => null,
            'lng' => null,
            'pris' => 'Fri entré',
            'ticket_url' => null,
            'status' => ContentStatus::Draft,
            'published_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (Event $event) => $event->setSlug('sv', Str::slug($event->namn)));
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function allDay(): static
    {
        return $this->state(fn () => ['all_day' => true]);
    }
}
