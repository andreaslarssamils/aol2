<?php

use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\Kommun;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('fills kommun_id from the business location when none is given', function () {
    $ludvika = Kommun::factory()->create(['namn' => 'Ludvika']);
    $location = BusinessLocation::factory()->create(['kommun_id' => $ludvika]);

    $event = Event::factory()->create([
        'kommun_id' => null,
        'business_location_id' => $location,
    ]);

    expect($event->fresh()->kommun_id)->toBe($ludvika->id);
});

it('never overwrites an explicitly set kommun_id', function () {
    $ludvika = Kommun::factory()->create(['namn' => 'Ludvika']);
    $smedjebacken = Kommun::factory()->create(['namn' => 'Smedjebacken']);
    $arrangor = BusinessLocation::factory()->create(['kommun_id' => $ludvika]);

    // Arrangör i Ludvika, eventet äger rum i Smedjebacken.
    $event = Event::factory()->create([
        'kommun_id' => $smedjebacken,
        'business_location_id' => $arrangor,
    ]);

    expect($event->fresh()->kommun_id)->toBe($smedjebacken->id);

    $event->update(['namn' => 'Nytt namn']);

    expect($event->fresh()->kommun_id)->toBe($smedjebacken->id);
});

it('keeps the given kommun when there is no business location', function () {
    $kommun = Kommun::factory()->create();

    $event = Event::factory()->create(['kommun_id' => $kommun, 'business_location_id' => null]);

    expect($event->kommun_id)->toBe($kommun->id);
});

it('links occurrences of a recurring event to their parent', function () {
    $parent = Event::factory()->create(['recurrence_rule' => 'FREQ=WEEKLY;BYDAY=TU']);
    $first = Event::factory()->create(['parent_event_id' => $parent, 'starts_at' => now()->addWeek()]);
    $second = Event::factory()->create(['parent_event_id' => $parent, 'starts_at' => now()->addWeeks(2)]);

    expect($parent->occurrences->pluck('id')->all())->toBe([$first->id, $second->id])
        ->and($first->parent->is($parent))->toBeTrue()
        ->and($first->recurrence_rule)->toBeNull();
});
