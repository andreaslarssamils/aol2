<?php

use App\Enums\BusinessUserRole;
use App\Filament\Portal\Pages\Anvandare;
use App\Models\Business;
use App\Models\User;
use App\Notifications\BusinessInvitation;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists the business\'s users with their roles', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $manager = User::factory()->verksamhetsagare()->create();
    $business->addUser($manager, BusinessUserRole::Manager);
    $outsider = User::factory()->verksamhetsagare()->create();
    actingAsPortalUser($owner, $business);

    $this->get(Anvandare::getUrl(tenant: $business))->assertOk();

    Livewire::test(Anvandare::class)
        ->assertCanSeeTableRecords([$owner, $manager])
        ->assertCanNotSeeTableRecords([$outsider])
        ->assertSee('Ägare')
        ->assertSee('Administratör');
});

it('lets the owner invite a new user by e-mail, creating the account and mailing a password link', function () {
    Notification::fake();
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    actingAsPortalUser($owner, $business);

    Livewire::test(Anvandare::class)
        ->callAction(TestAction::make('invite')->table(), [
            'email' => 'Kollega@Example.com',
            'name' => 'Kim Kollega',
            'role' => BusinessUserRole::Manager->value,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified('Inbjudan är skickad');

    $invited = User::query()->where('email', 'kollega@example.com')->sole();

    expect($invited->name)->toBe('Kim Kollega')
        ->and($invited->hasRole(User::ROLE_VERKSAMHETSAGARE))->toBeTrue()
        ->and($business->users()->whereKey($invited->id)->first()->pivot->role)->toBe(BusinessUserRole::Manager->value);

    Notification::assertSentTo($invited, BusinessInvitation::class, fn (BusinessInvitation $notification): bool => $notification->isNewUser
        && $notification->business->is($business)
        && str_contains($notification->url, '/portal/password-reset/reset'));
});

it('hides invite and remove from a manager', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $manager = User::factory()->verksamhetsagare()->create();
    $business->addUser($manager, BusinessUserRole::Manager);
    actingAsPortalUser($manager, $business);

    Livewire::test(Anvandare::class)
        ->assertCanSeeTableRecords([$owner, $manager])
        ->assertActionHidden(TestAction::make('invite')->table())
        ->assertActionHidden(TestAction::make('remove')->table($owner));
});

it('lets the owner remove another user but not themselves', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $manager = User::factory()->verksamhetsagare()->create();
    $business->addUser($manager, BusinessUserRole::Manager);
    actingAsPortalUser($owner, $business);

    Livewire::test(Anvandare::class)
        ->assertActionHidden(TestAction::make('remove')->table($owner))
        ->callAction(TestAction::make('remove')->table($manager))
        ->assertHasNoActionErrors()
        ->assertNotified('Användaren är borttagen')
        ->assertCanNotSeeTableRecords([$manager]);

    $this->assertDatabaseMissing('business_user', ['business_id' => $business->id, 'user_id' => $manager->id]);
    $this->assertDatabaseHas('business_user', ['business_id' => $business->id, 'user_id' => $owner->id]);
    expect($manager->fresh())->not->toBeNull();
});

it('is not reachable under another business', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    businessOwnedBy($owner);
    $other = Business::factory()->create();

    $this->actingAs($owner)->get("/portal/{$other->id}/anvandare")->assertNotFound();
});
