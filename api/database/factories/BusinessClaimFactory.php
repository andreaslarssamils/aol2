<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessClaim>
 */
class BusinessClaimFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory(),
            'status' => ClaimStatus::Pending,
            'verified_via' => null,
            'message' => fake()->sentence(),
            'handled_by' => null,
            'handled_at' => null,
        ];
    }
}
