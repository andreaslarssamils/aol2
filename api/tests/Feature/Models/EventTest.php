<?php

use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\Kommun;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('syncs kommun_id from the business location', function () {
    $ludvika = Kommun::factory()->create(['namn' => 'Ludvika']);
    $falun = Kommun::factory()->create(['namn' => 'Falun']);
    $location = BusinessLocation::factory()->create(['kommun_id' => $ludvika]);

    $event = Event::factory()->create([
        'kommun_id' => $falun, // fel kommun – ska skrivas över
        'business_location_id' => $location,
    ]);

    expect($event->fresh()->kommun_id)->toBe($ludvika->id);
});

it('keeps the given kommun when there is no business location', function () {
    $kommun = Kommun::factory()->create();

    $event = Event::factory()->create(['kommun_id' => $kommun, 'business_location_id' => null]);

    expect($event->kommun_id)->toBe($kommun->id);
});

it('frees the slug after soft delete', function () {
    $kommun = Kommun::factory()->create();
    $first = Event::factory()->create(['kommun_id' => $kommun, 'slug' => 'midsommar']);

    expect(fn () => DB::transaction(fn () => Event::factory()->create(['kommun_id' => $kommun, 'slug' => 'midsommar'])))
        ->toThrow(UniqueConstraintViolationException::class);

    $first->delete();

    expect(Event::factory()->create(['kommun_id' => $kommun, 'slug' => 'midsommar'])->exists)->toBeTrue();
});
