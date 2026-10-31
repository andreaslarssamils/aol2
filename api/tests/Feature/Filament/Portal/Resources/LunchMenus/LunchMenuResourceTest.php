<?php

use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Filament\Portal\Resources\LunchMenus\LunchMenuResource;
use App\Filament\Portal\Resources\LunchMenus\Pages\CreateLunchMenu;
use App\Filament\Portal\Resources\LunchMenus\Pages\EditLunchMenu;
use App\Filament\Portal\Resources\LunchMenus\Pages\ListLunchMenus;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\LunchMenu;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists only menus on the current business\'s locations and 404s on others', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $mine = businessOwnedBy($owner);
    $myMenu = LunchMenu::factory()->for(BusinessLocation::factory()->for($mine), 'businessLocation')->create();
    $other = Business::factory()->create();
    $theirMenu = LunchMenu::factory()->for(BusinessLocation::factory()->for($other), 'businessLocation')->create();
    actingAsPortalUser($owner, $mine);

    $this->get(LunchMenuResource::getUrl('index', tenant: $mine))->assertOk();
    $this->get(LunchMenuResource::getUrl('edit', ['record' => $theirMenu], tenant: $mine))->assertNotFound();
    $this->get(LunchMenuResource::getUrl('edit', ['record' => $theirMenu], tenant: $other))->assertNotFound();

    Livewire::test(ListLunchMenus::class)
        ->assertCanSeeTableRecords([$myMenu])
        ->assertCanNotSeeTableRecords([$theirMenu]);
});

it('sends a new menu for review when the business is not auto-approved', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $location = BusinessLocation::factory()->for($business)->create();
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateLunchMenu::class)
        ->fillForm([
            'business_location_id' => $location->id,
            'iso_year' => 2026,
            'week' => 45,
            'menu' => [
                'mon' => [['dish' => 'Köttbullar med potatismos']],
                'tue' => [['dish' => '  Fisk & chips ']],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Skickad för granskning');

    $menu = LunchMenu::sole();

    expect($menu->business_location_id)->toBe($location->id)
        ->and($menu->status)->toBe(ContentStatus::Draft)
        ->and($menu->menu)->toEqual(['mon' => ['Köttbullar med potatismos'], 'tue' => ['Fisk & chips'], 'wed' => [], 'thu' => [], 'fri' => []]);

    $revision = Revision::sole();

    expect($revision->status)->toBe(RevisionStatus::Pending)
        ->and($revision->payload['week'])->toBe(45)
        ->and($revision->payload['menu']['tue'])->toBe(['Fisk & chips']);
});

it('saves a new menu directly as published when the business has auto_approve', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner, attributes: ['auto_approve' => true]);
    $location = BusinessLocation::factory()->for($business)->create();
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateLunchMenu::class)
        ->fillForm([
            'business_location_id' => $location->id,
            'iso_year' => 2026,
            'week' => 45,
            'menu' => ['mon' => [['dish' => 'Ärtsoppa & pannkakor']]],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Publicerad');

    $menu = LunchMenu::sole();

    expect($menu->status)->toBe(ContentStatus::Published)
        ->and($menu->published_at)->not->toBeNull()
        ->and($menu->menu['mon'])->toBe(['Ärtsoppa & pannkakor']);

    $this->assertDatabaseCount('revisions', 0);
});

it('updates an existing menu directly with auto_approve and via revision without', function (bool $autoApprove) {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner, attributes: ['auto_approve' => $autoApprove]);
    $menu = LunchMenu::factory()
        ->for(BusinessLocation::factory()->for($business), 'businessLocation')
        ->create(['iso_year' => 2026, 'week' => 45, 'menu' => ['mon' => ['Gammal rätt']]]);
    actingAsPortalUser($owner, $business);

    Livewire::test(EditLunchMenu::class, ['record' => $menu->getRouteKey()])
        ->fillForm(['menu' => ['mon' => [['dish' => 'Ny rätt']], 'tue' => [['dish' => 'Tisdagsrätt']]]])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified($autoApprove ? 'Publicerad' : 'Skickad för granskning');

    $menu->refresh();

    if ($autoApprove) {
        expect($menu->menu['mon'])->toBe(['Ny rätt'])
            ->and($menu->status)->toBe(ContentStatus::Published);
        $this->assertDatabaseCount('revisions', 0);
    } else {
        expect($menu->menu['mon'])->toBe(['Gammal rätt'])
            ->and($menu->status)->toBe(ContentStatus::Draft)
            ->and(Revision::sole()->payload['menu']['tue'])->toBe(['Tisdagsrätt']);
    }
})->with([
    'auto_approve' => true,
    'review' => false,
]);

it('copies the dishes from the previous week', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner, attributes: ['auto_approve' => true]);
    $location = BusinessLocation::factory()->for($business)->create();
    LunchMenu::factory()->for($location, 'businessLocation')->create(['iso_year' => 2026, 'week' => 44, 'menu' => ['mon' => ['Ärtsoppa'], 'fri' => ['Fredagsmys']]]);
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateLunchMenu::class)
        ->fillForm(['business_location_id' => $location->id, 'iso_year' => 2026, 'week' => 45])
        ->callAction('copyPreviousWeek')
        ->assertNotified('Rätterna från v. 44 är kopierade')
        ->assertFormSet(function (array $state): void {
            // Enkla repeatrar håller varje rad som [fältnamn => värde] tills formuläret dehydreras.
            expect(array_column($state['menu']['mon'], 'dish'))->toBe(['Ärtsoppa'])
                ->and(array_column($state['menu']['fri'], 'dish'))->toBe(['Fredagsmys']);
        })
        ->call('create')
        ->assertHasNoFormErrors();

    expect(LunchMenu::query()->where('week', 45)->sole()->menu['mon'])->toBe(['Ärtsoppa']);
});

it('tells the owner when there is no previous week to copy', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $location = BusinessLocation::factory()->for($business)->create();
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateLunchMenu::class)
        ->fillForm(['business_location_id' => $location->id, 'iso_year' => 2026, 'week' => 2])
        ->callAction('copyPreviousWeek')
        ->assertNotified('Det finns ingen lunchmeny för v. 1 2026');
});

it('rejects a location of another business', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    BusinessLocation::factory()->for($business)->create();
    $theirLocation = BusinessLocation::factory()->create();
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateLunchMenu::class)
        ->fillForm(['business_location_id' => $theirLocation->id, 'iso_year' => 2026, 'week' => 45, 'menu' => ['mon' => [['dish' => 'Rätt']]]])
        ->call('create')
        ->assertHasFormErrors(['business_location_id']);

    $this->assertDatabaseCount('lunch_menus', 0);
});

it('rejects a second menu for the same week and location', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $location = BusinessLocation::factory()->for($business)->create();
    LunchMenu::factory()->for($location, 'businessLocation')->create(['iso_year' => 2026, 'week' => 45]);
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateLunchMenu::class)
        ->fillForm(['business_location_id' => $location->id, 'iso_year' => 2026, 'week' => 45, 'menu' => ['mon' => [['dish' => 'Rätt']]]])
        ->call('create')
        ->assertHasFormErrors(['week' => 'unique']);

    $this->assertDatabaseCount('lunch_menus', 1);
});
