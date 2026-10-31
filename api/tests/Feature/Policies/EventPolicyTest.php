<?php

use App\Enums\BusinessUserRole;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves the business through the event\'s location', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $manager = User::factory()->verksamhetsagare()->create();
    $business->addUser($manager, BusinessUserRole::Manager);
    $outsider = User::factory()->verksamhetsagare()->create();
    businessOwnedBy($outsider);

    $onLocation = Event::factory()->create(['business_location_id' => BusinessLocation::factory()->for($business)->create()->id]);
    $withoutLocation = Event::factory()->create();

    expect($owner->can('update', $onLocation))->toBeTrue()
        ->and($manager->can('update', $onLocation))->toBeTrue()
        ->and($outsider->can('update', $onLocation))->toBeFalse()
        ->and($owner->can('update', $withoutLocation))->toBeFalse()
        ->and($owner->can('delete', $onLocation))->toBeFalse()
        ->and(User::factory()->redaktor()->create()->can('update', $withoutLocation))->toBeTrue();
});
