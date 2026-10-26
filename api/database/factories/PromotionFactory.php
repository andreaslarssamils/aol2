<?php

namespace Database\Factories;

use App\Enums\PromotionPlacement;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'promotable_type' => (new Event)->getMorphClass(),
            'promotable_id' => Event::factory(),
            'kommun_id' => Kommun::factory(),
            'placement' => PromotionPlacement::Startsida,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
            'priority' => 0,
            'is_paid' => false,
            'price' => null,
            'invoiced_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function paid(float $price = 500): static
    {
        return $this->state(fn () => ['is_paid' => true, 'price' => $price]);
    }
}
