<?php

namespace Database\Factories;

use App\Enums\RevisionStatus;
use App\Models\Business;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Revision>
 */
class RevisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'revisionable_type' => (new Business)->getMorphClass(),
            'revisionable_id' => Business::factory(),
            'submitted_by' => User::factory(),
            'payload' => ['namn' => fake()->company()],
            'status' => RevisionStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_comment' => null,
            'applied_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => RevisionStatus::Approved,
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
        ]);
    }
}
