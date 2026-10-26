<?php

use App\Enums\ContentStatus;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists events for the editorial team', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $events = Event::factory()->count(2)->create();

    $this->get(EventResource::getUrl('index'))->assertOk();

    Livewire::test(ListEvents::class)
        ->assertCanSeeTableRecords($events);
});

it('filters the list on category', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $musik = Category::factory()->event()->create();
    $concert = Event::factory()->create();
    $concert->categories()->attach($musik);
    $other = Event::factory()->create();

    Livewire::test(ListEvents::class)
        ->filterTable('categories', [$musik->id])
        ->assertCanSeeTableRecords([$concert])
        ->assertCanNotSeeTableRecords([$other]);
});

it('creates an event with categories, a recurrence rule and a slug from its name', function () {
    $editor = User::factory()->redaktor()->create();
    $this->actingAs($editor);
    $kommun = Kommun::factory()->create();
    $category = Category::factory()->event()->create();

    Livewire::test(CreateEvent::class)
        ->fillForm([
            'namn' => 'Pubquiz på Stadshotellet',
            'kommun_id' => $kommun->id,
            'starts_at' => '2026-11-03 19:00:00',
            'ends_at' => '2026-11-03 21:00:00',
            'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=TU',
            'pris' => 'Fri entré',
            'categories' => [$category->id],
            'beskrivning.sv' => '<p>Quiz varje tisdag.</p>',
            'status' => ContentStatus::Published->value,
            'published_at' => '2026-10-26 12:00:00',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $event = Event::sole();

    expect($event->recurrence_rule)->toBe('FREQ=WEEKLY;BYDAY=TU')
        ->and($event->created_by)->toBe($editor->id)
        ->and($event->kommun_id)->toBe($kommun->id)
        ->and($event->categories->pluck('id')->all())->toBe([$category->id])
        ->and($event->slugFor('sv'))->toBe('pubquiz-pa-stadshotellet')
        ->and($event->slugFor('en'))->toBe('pubquiz-pa-stadshotellet');
});

it('fills kommun from the business location when none is given', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $location = BusinessLocation::factory()->create();

    Livewire::test(CreateEvent::class)
        ->fillForm([
            'namn' => 'Lunchkonsert',
            'business_location_id' => $location->id,
            'kommun_id' => null,
            'starts_at' => '2026-11-03 12:00:00',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Event::sole()->kommun_id)->toBe($location->kommun_id);
});

it('updates an event and keeps the renamed slug as a redirect', function () {
    $this->actingAs(User::factory()->admin()->create());
    $event = Event::factory()->create(['namn' => 'Pubquiz']);

    Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
        ->assertSchemaStateSet(['namn' => 'Pubquiz', 'slugs.sv' => 'pubquiz'])
        ->fillForm([
            'namn' => 'Pubquiz tisdagar',
            'slugs.sv' => 'pubquiz-tisdagar',
            'all_day' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $event->refresh();

    expect($event->namn)->toBe('Pubquiz tisdagar')
        ->and($event->all_day)->toBeTrue()
        ->and($event->slugFor('sv'))->toBe('pubquiz-tisdagar');

    $this->assertDatabaseHas('slugs', [
        'slugable_id' => $event->id,
        'slug' => 'pubquiz',
        'is_canonical' => false,
    ]);
});

it('forbids users without an editorial role', function (User $user) {
    $this->actingAs($user)
        ->get(EventResource::getUrl('index'))
        ->assertForbidden();
})->with([
    'no role' => fn () => User::factory()->create(),
    'verksamhetsägare' => fn () => User::factory()->verksamhetsagare()->create(),
]);
