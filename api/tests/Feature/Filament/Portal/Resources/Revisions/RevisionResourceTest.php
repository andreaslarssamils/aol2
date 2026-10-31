<?php

use App\Enums\BusinessUserRole;
use App\Filament\Portal\Resources\Revisions\Pages\ListRevisions;
use App\Filament\Portal\Resources\Revisions\RevisionResource;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\LunchMenu;
use App\Models\Offer;
use App\Models\Revision;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists only the user\'s own revisions for the current business, across content types', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $colleague = User::factory()->verksamhetsagare()->create();
    $business->addUser($colleague, BusinessUserRole::Manager);
    $location = BusinessLocation::factory()->for($business)->create();
    $other = Business::factory()->create();

    $mine = [
        Revision::factory()->for($business, 'revisionable')->create(['submitted_by' => $owner->id]),
        Revision::factory()->for($location, 'revisionable')->create(['submitted_by' => $owner->id]),
        Revision::factory()->for(Offer::factory()->for($business)->create(), 'revisionable')->create(['submitted_by' => $owner->id]),
        Revision::factory()->for(Event::factory()->create(['business_location_id' => $location->id]), 'revisionable')->create(['submitted_by' => $owner->id]),
        Revision::factory()->for(LunchMenu::factory()->for($location, 'businessLocation')->create(), 'revisionable')->create(['submitted_by' => $owner->id]),
    ];
    $colleagues = Revision::factory()->for($business, 'revisionable')->create(['submitted_by' => $colleague->id]);
    $mineElsewhere = Revision::factory()->for($other, 'revisionable')->create(['submitted_by' => $owner->id]);
    $mineElsewhereOffer = Revision::factory()->for(Offer::factory()->for($other)->create(), 'revisionable')->create(['submitted_by' => $owner->id]);
    actingAsPortalUser($owner, $business);

    $this->get(RevisionResource::getUrl('index', tenant: $business))->assertOk();

    Livewire::test(ListRevisions::class)
        ->assertCanSeeTableRecords($mine)
        ->assertCanNotSeeTableRecords([$colleagues, $mineElsewhere, $mineElsewhereOffer]);
});

it('shows status, the reviewer\'s comment and the diff', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner, attributes: ['namn' => 'Gamla namnet']);
    $revision = Revision::factory()->rejected()->for($business, 'revisionable')->create([
        'submitted_by' => $owner->id,
        'payload' => ['namn' => 'Föreslaget namn'],
        'review_comment' => 'Namnet stämmer inte med bolagsregistret.',
    ]);
    actingAsPortalUser($owner, $business);

    Livewire::test(ListRevisions::class)
        ->assertSee('Avvisad')
        ->assertSee('Namnet stämmer inte med bolagsregistret.')
        ->mountAction(TestAction::make('diff')->table($revision))
        ->assertMountedActionModalSee('Gamla namnet')
        ->assertMountedActionModalSee('Föreslaget namn');
});

it('is read-only', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    actingAsPortalUser($owner, $business);

    expect(RevisionResource::canCreate())->toBeFalse()
        ->and(RevisionResource::hasPage('edit'))->toBeFalse();

    $this->get(RevisionResource::getUrl('index', tenant: $business).'/create')->assertNotFound();
});
