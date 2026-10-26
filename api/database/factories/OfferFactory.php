<?php

namespace Database\Factories;

use App\Enums\ContentStatus;
use App\Enums\DiscountType;
use App\Models\Business;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    public function definition(): array
    {
        $titel = fake()->sentence(3);

        return [
            'business_id' => Business::factory(),
            'titel' => ['sv' => $titel, 'en' => $titel],
            'beskrivning' => ['sv' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'discount_type' => DiscountType::Percentage,
            'discount_value' => 10,
            'code' => null,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => ContentStatus::Draft,
        ];
    }
}
