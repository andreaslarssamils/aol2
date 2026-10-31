<?php

use App\Actions\Revisions\SubmitRevision;
use App\Enums\BusinessUserRole;
use App\Enums\ContentStatus;
use App\Enums\DiscountType;
use App\Enums\RevisionStatus;
use App\Filament\Portal\Resources\Offers\OfferResource;
use App\Filament\Portal\Resources\Offers\Pages\CreateOffer;
use App\Filament\Portal\Resources\Offers\Pages\EditOffer;
use App\Filament\Portal\Resources\Offers\Pages\ListOffers;
use App\Filament\Portal\Schemas\Components\PendingRevisionBanner;
use App\Models\Business;
use App\Models\Offer;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists only the current business\'s offers', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $mine = businessOwnedBy($owner);
    $myOffer = Offer::factory()->for($mine)->create();
    $theirOffer = Offer::factory()->create();
    actingAsPortalUser($owner, $mine);

    $this->get(OfferResource::getUrl('index', tenant: $mine))->assertOk();

    Livewire::test(ListOffers::class)
        ->assertCanSeeTableRecords([$myOffer])
        ->assertCanNotSeeTableRecords([$theirOffer]);
});

it('returns 404 for another business\'s offer by direct URL, under either tenant', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $mine = businessOwnedBy($owner);
    $other = Business::factory()->create();
    $theirOffer = Offer::factory()->for($other)->create();
    actingAsPortalUser($owner, $mine);

    $this->get(OfferResource::getUrl('edit', ['record' => $theirOffer], tenant: $mine))->assertNotFound();
    $this->get(OfferResource::getUrl('edit', ['record' => $theirOffer], tenant: $other))->assertNotFound();
    $this->get(OfferResource::getUrl('create', tenant: $other))->assertNotFound();

    expect(fn () => Livewire::test(EditOffer::class, ['record' => $theirOffer->getRouteKey()]))
        ->toThrow(ModelNotFoundException::class);
});

it('submits an edit as a pending revision and leaves the row untouched', function (BusinessUserRole $role) {
    $user = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($user, $role);
    $offer = Offer::factory()->for($business)->create(['titel' => ['sv' => 'Gammal titel', 'en' => 'Old title'], 'code' => null]);
    actingAsPortalUser($user, $business);

    Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
        ->fillForm(['titel.sv' => 'Ny titel', 'code' => 'HÖST26'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Skickad för granskning');

    $revision = Revision::sole();

    expect($revision->status)->toBe(RevisionStatus::Pending)
        ->and($revision->submitted_by)->toBe($user->id)
        ->and($revision->revisionable->is($offer))->toBeTrue()
        ->and($revision->payload['titel'])->toEqual(['sv' => 'Ny titel', 'en' => 'Old title'])
        ->and($revision->payload['code'])->toBe('HÖST26');

    $offer->refresh();

    expect($offer->titel['sv'])->toBe('Gammal titel')
        ->and($offer->code)->toBeNull()
        ->and($offer->status)->toBe(ContentStatus::Draft);
})->with([
    'owner' => BusinessUserRole::Owner,
    'manager' => BusinessUserRole::Manager,
]);

it('publishes immediately when the business has auto_approve', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner, attributes: ['auto_approve' => true]);
    $offer = Offer::factory()->for($business)->create(['titel' => ['sv' => 'Gammal titel', 'en' => 'Old title']]);
    actingAsPortalUser($owner, $business);

    Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
        ->fillForm(['titel.sv' => 'Ny titel'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Publicerad');

    $offer->refresh();

    expect($offer->titel['sv'])->toBe('Ny titel')
        ->and($offer->status)->toBe(ContentStatus::Published)
        ->and(Revision::sole()->status)->toBe(RevisionStatus::Approved);
});

it('shows the pending banner and replaces the pending revision on resubmission', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $offer = Offer::factory()->for($business)->create(['titel' => ['sv' => 'Gammal titel', 'en' => 'Old title']]);
    $first = Revision::factory()->for($offer, 'revisionable')->create(['submitted_by' => $owner->id, 'payload' => ['titel' => ['sv' => 'Första', 'en' => 'First']]]);
    actingAsPortalUser($owner, $business);

    Livewire::test(EditOffer::class, ['record' => $offer->getRouteKey()])
        ->assertSee(PendingRevisionBanner::HEADING)
        ->fillForm(['titel.sv' => 'Andra'])
        ->call('save')
        ->assertHasNoFormErrors();

    $first->refresh();

    expect($first->status)->toBe(RevisionStatus::Rejected)
        ->and($first->review_comment)->toBe(SubmitRevision::SUPERSEDED_COMMENT)
        ->and(Revision::query()->pending()->sole()->payload['titel']['sv'])->toBe('Andra')
        ->and($offer->fresh()->titel['sv'])->toBe('Gammal titel');
});

it('creates a new offer as a draft under the current business with a pending revision', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateOffer::class)
        ->fillForm([
            'titel.sv' => 'Två för en',
            'beskrivning.sv' => '<p>Gäller vardagar.</p>',
            'discount_type' => DiscountType::Percentage->value,
            'discount_value' => 50,
            'starts_at' => '2026-11-01 00:00:00',
            'ends_at' => '2026-11-30 23:59:00',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Skickad för granskning');

    $offer = Offer::withoutGlobalScopes()->sole();

    expect($offer->business_id)->toBe($business->id)
        ->and($offer->status)->toBe(ContentStatus::Draft)
        ->and($offer->titel['sv'])->toBe('Två för en')
        ->and($offer->discount_type)->toBe(DiscountType::Percentage);

    $revision = Revision::sole();

    expect($revision->status)->toBe(RevisionStatus::Pending)
        ->and($revision->revisionable->is($offer))->toBeTrue()
        ->and($revision->payload['business_id'])->toBe($business->id);
});

it('requires a title in the default language', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateOffer::class)
        ->fillForm(['titel.sv' => '', 'titel.en' => 'Only english'])
        ->call('create')
        ->assertHasFormErrors(['titel.sv' => 'required']);

    $this->assertDatabaseCount('offers', 0);
});
