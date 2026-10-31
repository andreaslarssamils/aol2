<?php

use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Filament\Portal\Resources\Events\EventResource;
use App\Filament\Portal\Resources\Events\Pages\CreateEvent;
use App\Filament\Portal\Resources\Events\Pages\EditEvent;
use App\Filament\Portal\Resources\Events\Pages\ListEvents;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists only events on the current business\'s locations and 404s on others', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $mine = businessOwnedBy($owner);
    $myEvent = Event::factory()->create(['business_location_id' => BusinessLocation::factory()->for($mine)->create()->id]);
    $other = Business::factory()->create();
    $theirEvent = Event::factory()->create(['business_location_id' => BusinessLocation::factory()->for($other)->create()->id]);
    $noLocation = Event::factory()->create();
    actingAsPortalUser($owner, $mine);

    $this->get(EventResource::getUrl('index', tenant: $mine))->assertOk();
    $this->get(EventResource::getUrl('edit', ['record' => $theirEvent], tenant: $mine))->assertNotFound();
    $this->get(EventResource::getUrl('edit', ['record' => $noLocation], tenant: $mine))->assertNotFound();
    $this->get(EventResource::getUrl('edit', ['record' => $theirEvent], tenant: $other))->assertNotFound();

    Livewire::test(ListEvents::class)
        ->assertCanSeeTableRecords([$myEvent])
        ->assertCanNotSeeTableRecords([$theirEvent, $noLocation]);
});

it('prefills kommun from the chosen location but lets the owner change it', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $location = BusinessLocation::factory()->for($business)->create();
    $otherKommun = Kommun::factory()->create();
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateEvent::class)
        ->fillForm(['business_location_id' => $location->id])
        ->assertFormSet(['kommun_id' => $location->kommun_id])
        ->fillForm(['kommun_id' => $otherKommun->id])
        ->assertFormSet(['kommun_id' => $otherKommun->id])
        ->assertFormFieldDoesNotExist('status');
});

it('creates an event as a draft on the business\'s location with pending images and a revision', function () {
    Storage::fake('public');
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $location = BusinessLocation::factory()->for($business)->create();
    $category = Category::factory()->event()->create();
    actingAsPortalUser($owner, $business);

    Livewire::test(CreateEvent::class)
        ->fillForm([
            'namn' => 'Pubquiz',
            'business_location_id' => $location->id,
            'starts_at' => '2026-11-20 19:00:00',
            'ends_at' => '2026-11-20 21:00:00',
            'pris' => 'Fri entré',
            'categories' => [$category->id],
            'beskrivning.sv' => '<p>Frågesport varje fredag.</p>',
            'pending' => [UploadedFile::fake()->image('affisch.jpg')],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified('Skickad för granskning');

    $event = Event::sole();

    expect($event->business_location_id)->toBe($location->id)
        ->and($event->kommun_id)->toBe($location->kommun_id)
        ->and($event->created_by)->toBe($owner->id)
        ->and($event->status)->toBe(ContentStatus::Draft)
        ->and($event->categories)->toHaveCount(0)
        ->and($event->getMedia(Business::MEDIA_PENDING))->toHaveCount(1)
        ->and($event->getMedia(Business::MEDIA_IMAGES))->toHaveCount(0);

    $revision = Revision::sole();

    expect($revision->status)->toBe(RevisionStatus::Pending)
        ->and($revision->revisionable->is($event))->toBeTrue()
        ->and($revision->payload['categories'])->toBe([$category->id])
        ->and($revision->payload['business_location_id'])->toBe($location->id);
});

it('submits an edit for review and leaves the event unchanged', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    $event = Event::factory()->create(['namn' => 'Gamla namnet', 'business_location_id' => BusinessLocation::factory()->for($business)->create()->id]);
    actingAsPortalUser($owner, $business);

    Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
        ->fillForm(['namn' => 'Nya namnet'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Skickad för granskning');

    expect($event->fresh()->namn)->toBe('Gamla namnet')
        ->and(Revision::sole()->payload['namn'])->toBe('Nya namnet');
});
