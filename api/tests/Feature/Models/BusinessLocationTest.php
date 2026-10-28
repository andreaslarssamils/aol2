<?php

use App\Enums\ContentStatus;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Kommun;
use App\Models\OpeningHour;
use App\Models\OpeningHourException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('allows several locations for one business in the same kommun', function () {
    $business = Business::factory()->create();
    $kommun = Kommun::factory()->create();

    BusinessLocation::factory()->for($business)->create(['kommun_id' => $kommun, 'namn' => 'Acme Centrum']);
    BusinessLocation::factory()->for($business)->secondary()->create(['kommun_id' => $kommun, 'namn' => 'Acme Lyviksberget']);

    expect($business->locations()->where('kommun_id', $kommun->id)->count())->toBe(2);
});

it('allows only one primary location per business', function () {
    $business = Business::factory()->create();
    BusinessLocation::factory()->for($business)->create(['is_primary' => true]);

    expect(fn () => DB::transaction(fn () => BusinessLocation::factory()->for($business)->create(['is_primary' => true])))
        ->toThrow(UniqueConstraintViolationException::class);

    BusinessLocation::factory()->for($business)->secondary()->create();
    BusinessLocation::factory()->create(['is_primary' => true]); // annat företag

    expect($business->locations)->toHaveCount(2)
        ->and($business->primaryLocation)->not->toBeNull();
});

it('frees the primary flag when the primary location is soft-deleted', function () {
    $business = Business::factory()->create();
    $old = BusinessLocation::factory()->for($business)->create(['is_primary' => true]);
    $old->delete();

    $new = BusinessLocation::factory()->for($business)->create(['is_primary' => true]);

    expect($business->primaryLocation->is($new))->toBeTrue()
        ->and(BusinessLocation::withTrashed()->where('business_id', $business->id)->count())->toBe(2);
});

it('falls back to the business name when the location has none', function () {
    $business = Business::factory()->create(['namn' => 'Acme AB']);
    $location = BusinessLocation::factory()->for($business)->create(['namn' => null]);

    expect($location->display_name)->toBe('Acme AB');

    $location->namn = 'Acme Ludvika';

    expect($location->display_name)->toBe('Acme Ludvika');
});

it('allows two opening intervals on the same weekday, sorted by opens', function () {
    $location = BusinessLocation::factory()->create();
    OpeningHour::factory()->for($location)->create(['weekday' => 1, 'opens' => '17:00', 'closes' => '21:00']);
    OpeningHour::factory()->for($location)->create(['weekday' => 1, 'opens' => '11:00', 'closes' => '14:00']);

    expect($location->openingHours()->where('weekday', 1)->pluck('opens')->all())
        ->toBe(['11:00:00', '17:00:00']);
});

it('lets an exception win over the regular hours for that date', function () {
    $location = BusinessLocation::factory()->create();
    $tuesday = Carbon::parse('next tuesday');
    OpeningHour::factory()->for($location)->create(['weekday' => 1, 'opens' => '09:00', 'closes' => '17:00']);
    OpeningHourException::factory()->for($location)->create(['date' => $tuesday, 'note' => 'Midsommarafton']);

    $onTheDay = $location->hoursOn($tuesday);
    $weekAfter = $location->hoursOn($tuesday->copy()->addWeek());

    expect($onTheDay)->toHaveCount(1)
        ->and($onTheDay->first())->toBeInstanceOf(OpeningHourException::class)
        ->and($onTheDay->first()->closed)->toBeTrue()
        ->and($weekAfter->first())->toBeInstanceOf(OpeningHour::class)
        ->and($weekAfter->first()->opens)->toBe('09:00:00');
});

it('shows only published locations of published businesses in the catalog', function () {
    $visible = BusinessLocation::factory()->for(Business::factory()->published())->create();
    $draftLocation = BusinessLocation::factory()->draft()->for(Business::factory()->published())->create();
    $draftBusiness = BusinessLocation::factory()->for(Business::factory())->create();
    $archivedBusiness = BusinessLocation::factory()->for(Business::factory()->create(['status' => ContentStatus::Archived]))->create();
    $closed = BusinessLocation::factory()->for(Business::factory()->published())->create();
    $closed->delete();
    $deletedBusiness = Business::factory()->published()->create();
    $orphan = BusinessLocation::factory()->for($deletedBusiness)->create();
    $deletedBusiness->delete();

    // published() ser bara till lokaliseringens egen status – företagets status och radering väger katalog-scopet in.
    expect(BusinessLocation::inCatalog()->pluck('id')->all())->toBe([$visible->id])
        ->and(BusinessLocation::published()->pluck('id')->all())->toEqualCanonicalizing([$visible->id, $draftBusiness->id, $archivedBusiness->id, $orphan->id]);
});

it('knows whether it is open at a given time, including intervals past midnight', function () {
    $location = BusinessLocation::factory()->create();
    OpeningHour::factory()->for($location)->create(['weekday' => 0, 'opens' => '09:00', 'closes' => '17:00']);
    OpeningHour::factory()->for($location)->create(['weekday' => 4, 'opens' => '22:00', 'closes' => '02:00']);

    expect($location->isOpenAt(Carbon::parse('2026-11-02 12:00', 'Europe/Stockholm')))->toBeTrue() // måndag
        ->and($location->isOpenAt(Carbon::parse('2026-11-02 17:00', 'Europe/Stockholm')))->toBeFalse()
        ->and($location->isOpenAt(Carbon::parse('2026-11-06 23:30', 'Europe/Stockholm')))->toBeTrue() // fredag natt
        ->and($location->isOpenAt(Carbon::parse('2026-11-07 01:30', 'Europe/Stockholm')))->toBeTrue() // efternatt mot lördag
        ->and($location->isOpenAt(Carbon::parse('2026-11-07 02:30', 'Europe/Stockholm')))->toBeFalse();
});

it('resolves the day for opening hours in the display timezone', function () {
    $location = BusinessLocation::factory()->create();
    OpeningHour::factory()->for($location)->create(['weekday' => 0, 'opens' => '09:00', 'closes' => '17:00']);
    OpeningHour::factory()->for($location)->create(['weekday' => 1, 'opens' => '10:00', 'closes' => '18:00']);

    // 23:30 UTC måndag 2026-11-02 är 00:30 tisdag i Sverige.
    expect($location->hoursOn(Carbon::parse('2026-11-02 23:30', 'UTC'))->first()->opens)->toBe('10:00:00')
        ->and($location->hoursOn(Carbon::parse('2026-11-02 12:00', 'UTC'))->first()->opens)->toBe('09:00:00');
});
