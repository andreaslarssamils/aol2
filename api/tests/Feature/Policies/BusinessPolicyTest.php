<?php

use App\Enums\BusinessUserRole;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('grants abilities on a business by role in business_user', function (?BusinessUserRole $role, ?string $platformRole, array $expected) {
    $business = Business::factory()->create();
    $factory = User::factory();
    $user = ($platformRole ? $factory->{$platformRole}() : $factory)->create();

    if ($role !== null) {
        $business->addUser($user, $role);
    }

    $actual = collect(['view', 'update', 'delete', 'inviteUser', 'removeUser'])
        ->mapWithKeys(fn (string $ability): array => [$ability => $user->can($ability, $business)])
        ->all();

    expect($actual)->toBe($expected)
        ->and($user->can('viewAny', Business::class))->toBe($expected['view'])
        ->and($user->can('create', Business::class))->toBe($platformRole === 'redaktor');
})->with([
    'owner' => [BusinessUserRole::Owner, 'verksamhetsagare', ['view' => true, 'update' => true, 'delete' => false, 'inviteUser' => true, 'removeUser' => true]],
    'manager' => [BusinessUserRole::Manager, 'verksamhetsagare', ['view' => true, 'update' => true, 'delete' => false, 'inviteUser' => false, 'removeUser' => false]],
    'outsider' => [null, 'verksamhetsagare', ['view' => false, 'update' => false, 'delete' => false, 'inviteUser' => false, 'removeUser' => false]],
    'redaktör' => [null, 'redaktor', ['view' => true, 'update' => true, 'delete' => true, 'inviteUser' => true, 'removeUser' => true]],
]);

it('does not count membership of another business', function () {
    $user = User::factory()->verksamhetsagare()->create();
    businessOwnedBy($user);
    $other = Business::factory()->create();

    expect($user->can('update', $other))->toBeFalse()
        ->and($user->can('inviteUser', $other))->toBeFalse();
});
