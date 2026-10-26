<?php

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Kommun;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('rejects two active locations with the same slug in the same kommun', function () {
    $kommun = Kommun::factory()->create();
    BusinessLocation::factory()->create(['kommun_id' => $kommun, 'slug' => 'acme']);

    expect(fn () => DB::transaction(fn () => BusinessLocation::factory()->create(['kommun_id' => $kommun, 'slug' => 'acme'])))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('lets a soft-deleted location release its slug', function () {
    $kommun = Kommun::factory()->create();
    $old = BusinessLocation::factory()->create(['kommun_id' => $kommun, 'slug' => 'acme']);
    $old->delete();

    $new = BusinessLocation::factory()->create(['kommun_id' => $kommun, 'slug' => 'acme']);

    expect($new->exists)->toBeTrue()
        ->and(BusinessLocation::withTrashed()->where('slug', 'acme')->count())->toBe(2);
});

it('allows the same slug in different kommuner', function () {
    BusinessLocation::factory()->create(['slug' => 'acme']);
    BusinessLocation::factory()->create(['slug' => 'acme']);

    expect(BusinessLocation::where('slug', 'acme')->count())->toBe(2);
});

it('falls back to the business name when the location has none', function () {
    $business = Business::factory()->create(['namn' => 'Acme AB']);
    $location = BusinessLocation::factory()->for($business)->create(['namn' => null]);

    expect($location->display_name)->toBe('Acme AB');

    $location->namn = 'Acme Ludvika';

    expect($location->display_name)->toBe('Acme Ludvika');
});
