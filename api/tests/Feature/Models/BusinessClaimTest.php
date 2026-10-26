<?php

use App\Enums\ClaimStatus;
use App\Enums\ManagedBy;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a business_user row and hands over management when approved', function () {
    $business = Business::factory()->create(['managed_by' => ManagedBy::Editorial]);
    $owner = User::factory()->create();
    $redaktor = User::factory()->create();

    $claim = BusinessClaim::factory()->for($business)->for($owner)->create(['message' => 'Det är mitt café']);
    $claim->approve($redaktor);

    $claim->refresh();

    expect($claim->status)->toBe(ClaimStatus::Approved)
        ->and($claim->handler->is($redaktor))->toBeTrue()
        ->and($claim->handled_at)->not->toBeNull()
        ->and($business->fresh()->managed_by)->toBe(ManagedBy::Self)
        ->and($business->users()->whereKey($owner->id)->first()->pivot->role)->toBe('owner')
        ->and(BusinessClaim::pending()->count())->toBe(0);
});

it('leaves the business untouched when rejected', function () {
    $business = Business::factory()->create(['managed_by' => ManagedBy::Editorial]);
    $claim = BusinessClaim::factory()->for($business)->create();

    $claim->reject(User::factory()->create());

    expect($claim->fresh()->status)->toBe(ClaimStatus::Rejected)
        ->and($business->fresh()->managed_by)->toBe(ManagedBy::Editorial)
        ->and($business->users)->toHaveCount(0);
});
