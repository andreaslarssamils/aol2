<?php

use App\Enums\BusinessUserRole;
use App\Models\Business;
use App\Models\Offer;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('grants content abilities to members of the offer\'s business and to the editorial team', function (?BusinessUserRole $role, ?string $platformRole, array $expected) {
    $business = Business::factory()->create();
    $offer = Offer::factory()->for($business)->create();
    $factory = User::factory();
    $user = ($platformRole ? $factory->{$platformRole}() : $factory)->create();

    if ($role !== null) {
        $business->addUser($user, $role);
    }

    $actual = collect(['view', 'update', 'delete', 'restore', 'forceDelete', 'replicate'])
        ->mapWithKeys(fn (string $ability): array => [$ability => $user->can($ability, $offer)])
        ->all();

    expect($actual)->toBe($expected);
})->with([
    'owner' => [BusinessUserRole::Owner, 'verksamhetsagare', ['view' => true, 'update' => true, 'delete' => false, 'restore' => false, 'forceDelete' => false, 'replicate' => false]],
    'manager' => [BusinessUserRole::Manager, 'verksamhetsagare', ['view' => true, 'update' => true, 'delete' => false, 'restore' => false, 'forceDelete' => false, 'replicate' => false]],
    'outsider' => [null, 'verksamhetsagare', ['view' => false, 'update' => false, 'delete' => false, 'restore' => false, 'forceDelete' => false, 'replicate' => false]],
    'admin' => [null, 'admin', ['view' => true, 'update' => true, 'delete' => true, 'restore' => true, 'forceDelete' => true, 'replicate' => true]],
]);

it('ties viewAny and create to the current tenant in the portal', function () {
    $member = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($member);
    $other = Business::factory()->create();

    Filament::setCurrentPanel(User::PANEL_PORTAL);
    Filament::setTenant($business, isQuiet: true);

    expect($member->can('viewAny', Offer::class))->toBeTrue()
        ->and($member->can('create', Offer::class))->toBeTrue()
        ->and($member->can('deleteAny', Offer::class))->toBeFalse();

    Filament::setTenant($other, isQuiet: true);

    expect($member->can('viewAny', Offer::class))->toBeFalse()
        ->and($member->can('create', Offer::class))->toBeFalse();
});

it('falls back to any membership outside a tenant context', function () {
    $member = User::factory()->verksamhetsagare()->create();
    businessOwnedBy($member);
    $outsider = User::factory()->verksamhetsagare()->create();

    expect($member->can('viewAny', Offer::class))->toBeTrue()
        ->and($outsider->can('viewAny', Offer::class))->toBeFalse()
        ->and($outsider->can('create', Offer::class))->toBeFalse();
});
