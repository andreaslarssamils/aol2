<?php

use App\Enums\ClaimStatus;
use App\Enums\ManagedBy;
use App\Filament\Portal\Tenancy\TaOverVerksamhet;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('offers only published, editorially managed businesses without an owner', function () {
    $claimable = Business::factory()->published()->create(['namn' => 'Café Lyktan']);
    Business::factory()->create(['namn' => 'Café Utkast']);
    Business::factory()->published()->create(['namn' => 'Café Själv', 'managed_by' => ManagedBy::Self]);
    Business::factory()->published()->create(['namn' => 'Café Ägd'])->addUser(User::factory()->create());

    expect(TaOverVerksamhet::claimableQuery()->pluck('id')->all())->toBe([$claimable->id]);
});

it('creates a pending claim with the message', function () {
    $user = User::factory()->verksamhetsagare()->create();
    $business = Business::factory()->published()->create(['namn' => 'Café Lyktan']);
    $this->actingAs($user);
    Filament::setCurrentPanel(User::PANEL_PORTAL);

    Livewire::test(TaOverVerksamhet::class)
        ->fillForm(['business_id' => $business->id, 'message' => 'Jag driver caféet sedan 2019.'])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertNotified('Anspråket är skickat');

    $claim = BusinessClaim::sole();

    expect($claim->user_id)->toBe($user->id)
        ->and($claim->business_id)->toBe($business->id)
        ->and($claim->status)->toBe(ClaimStatus::Pending)
        ->and($claim->message)->toBe('Jag driver caféet sedan 2019.')
        ->and($business->fresh()->users)->toHaveCount(0);
});

it('rejects a business that already has an owner', function () {
    $user = User::factory()->verksamhetsagare()->create();
    $owned = Business::factory()->published()->create();
    $owned->addUser(User::factory()->create());
    $this->actingAs($user);
    Filament::setCurrentPanel(User::PANEL_PORTAL);

    Livewire::test(TaOverVerksamhet::class)
        ->fillForm(['business_id' => $owned->id])
        ->call('register')
        ->assertHasFormErrors(['business_id']);

    $this->assertDatabaseCount('business_claims', 0);
});

it('does not create a second pending claim on the same business', function () {
    $user = User::factory()->verksamhetsagare()->create();
    $business = Business::factory()->published()->create(['namn' => 'Café Lyktan']);
    BusinessClaim::factory()->for($business)->for($user)->create();
    $this->actingAs($user);
    Filament::setCurrentPanel(User::PANEL_PORTAL);

    Livewire::test(TaOverVerksamhet::class)
        ->fillForm(['business_id' => $business->id])
        ->call('register')
        ->assertNotified('Du har redan ett väntande anspråk på Café Lyktan');

    $this->assertDatabaseCount('business_claims', 1);
});

it('lists the user\'s pending claims on the page', function () {
    $user = User::factory()->verksamhetsagare()->create();
    BusinessClaim::factory()->for(Business::factory()->published()->state(['namn' => 'Café Lyktan']))->for($user)->create();

    $this->actingAs($user)
        ->get('/portal/ta-over')
        ->assertOk()
        ->assertSee('Väntar på redaktionen: Café Lyktan');
});
