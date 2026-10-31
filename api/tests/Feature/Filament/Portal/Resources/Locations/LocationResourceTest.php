<?php

use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Filament\Portal\Resources\Locations\LocationResource;
use App\Filament\Portal\Resources\Locations\Pages\CreateLocation;
use App\Filament\Portal\Resources\Locations\Pages\EditLocation;
use App\Filament\Portal\Resources\Locations\Pages\ListLocations;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Kommun;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists only the current business\'s locations and 404s on others', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $mine = businessOwnedBy($owner);
    $myLocation = BusinessLocation::factory()->for($mine)->create();
    $other = Business::factory()->create();
    $theirLocation = BusinessLocation::factory()->for($other)->create();
    actingAsPortalUser($owner, $mine);

    $this->get(LocationResource::getUrl('index', tenant: $mine))->assertOk();
    $this->get(LocationResource::getUrl('edit', ['record' => $theirLocation], tenant: $mine))->assertNotFound();
    $this->get(LocationResource::getUrl('edit', ['record' => $theirLocation], tenant: $other))->assertNotFound();

    Livewire::test(ListLocations::class)
        ->assertCanSeeTableRecords([$myLocation])
        ->assertCanNotSeeTableRecords([$theirLocation]);
});

it('saves opening hours directly but sends address changes for review', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $location = BusinessLocation::factory()->for($business)->create(['adress' => 'Gamla gatan 1']);
    $location->openingHours()->create(['weekday' => 0, 'opens' => '09:00', 'closes' => '17:00']);
    actingAsPortalUser($owner, $business);

    Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
        ->fillForm([
            'adress' => 'Nya gatan 2',
            'openingHours' => [
                ['weekday' => 0, 'opens' => '10:00', 'closes' => '18:00', 'closed' => false],
                ['weekday' => 6, 'opens' => null, 'closes' => null, 'closed' => true],
            ],
            'openingHourExceptions' => [
                ['date' => '2026-12-24', 'opens' => null, 'closes' => null, 'closed' => true, 'note' => 'Julafton'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Skickad för granskning');

    $location->refresh();

    expect($location->adress)->toBe('Gamla gatan 1')
        ->and($location->openingHours->pluck('opens')->all())->toBe(['10:00:00', null])
        ->and($location->openingHours->pluck('closed')->all())->toBe([false, true])
        ->and($location->openingHourExceptions)->toHaveCount(1)
        ->and($location->openingHourExceptions->first()->note)->toBe('Julafton');

    $revision = Revision::sole();

    expect($revision->status)->toBe(RevisionStatus::Pending)
        ->and($revision->payload['adress'])->toBe('Nya gatan 2')
        ->and($revision->payload)->not->toHaveKeys(['openingHours', 'openingHourExceptions']);
});

it('shows the slug without a field to edit it', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $location = BusinessLocation::factory()->for($business)->create();
    actingAsPortalUser($owner, $business);

    Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
        ->assertSee($location->slugFor('sv'))
        ->assertFormFieldDoesNotExist('slugs.sv')
        ->assertFormFieldDoesNotExist('is_primary')
        ->assertFormFieldDoesNotExist('status');
});

it('creates a new location as a draft under the current business, not primary when one exists', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    BusinessLocation::factory()->for($business)->create();
    $kommun = Kommun::factory()->create();
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateLocation::class)
        ->fillForm([
            'kommun_id' => $kommun->id,
            'namn' => 'Acme Centrum',
            'adress' => 'Storgatan 1',
            'ort' => 'Ludvika',
            'openingHours' => [
                ['weekday' => 1, 'opens' => '09:00', 'closes' => '17:00', 'closed' => false],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Skickad för granskning');

    $location = BusinessLocation::withoutGlobalScopes()->where('adress', 'Storgatan 1')->sole();

    expect($location->business_id)->toBe($business->id)
        ->and($location->kommun_id)->toBe($kommun->id)
        ->and($location->is_primary)->toBeFalse()
        ->and($location->status)->toBe(ContentStatus::Draft)
        ->and($location->openingHours)->toHaveCount(1)
        ->and($location->slugFor('sv'))->toBeNull();

    $revision = Revision::sole();

    expect($revision->revisionable->is($location))->toBeTrue()
        ->and($revision->payload['business_id'])->toBe($business->id)
        ->and($revision->payload['namn'])->toBe('Acme Centrum');
});
