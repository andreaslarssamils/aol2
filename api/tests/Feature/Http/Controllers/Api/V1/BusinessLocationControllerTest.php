<?php

use App\Enums\ContentStatus;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\LunchMenu;
use App\Models\OpeningHour;
use App\Models\OpeningHourException;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lists locations of published businesses in the site kommuner, sorted by display name', function () {
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;

    BusinessLocation::factory()->for(Business::factory()->published()->create(['namn' => 'Bravo AB']))->create(['kommun_id' => $kommun, 'ort' => 'Ludvika']);
    BusinessLocation::factory()->for(Business::factory()->published()->create(['namn' => 'Zeta AB']))->create(['kommun_id' => $kommun, 'namn' => 'Alpha Centrum']);
    BusinessLocation::factory()->for(Business::factory()->create(['namn' => 'Utkast AB']))->create(['kommun_id' => $kommun]);
    BusinessLocation::factory()->for(Business::factory()->create(['namn' => 'Arkiv AB', 'status' => ContentStatus::Archived]))->create(['kommun_id' => $kommun]);
    BusinessLocation::factory()->for(Business::factory()->published()->create(['namn' => 'Annan kommun AB']))->create(['kommun_id' => Kommun::factory()]);
    $closed = BusinessLocation::factory()->for(Business::factory()->published()->create(['namn' => 'Nedlagd AB']))->create(['kommun_id' => $kommun]);
    $closed->delete();
    $deleted = Business::factory()->published()->create(['namn' => 'Raderad AB']);
    BusinessLocation::factory()->for($deleted)->create(['kommun_id' => $kommun]);
    $deleted->delete();

    $response = $this->getJson("/api/v1/{$site->slug}/businesses")->assertOk();

    expect($response->json('data.*.namn'))->toBe(['Alpha Centrum', 'Bravo AB'])
        ->and($response->json('meta.total'))->toBe(2)
        ->and($response->json('data.1.business.namn'))->toBe('Bravo AB')
        ->and($response->json('data.1.ort'))->toBe('Ludvika')
        ->and($response->json('data.1.kommun.id'))->toBe($kommun);
});

it('filters on category and searches by name', function () {
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;
    $cafe = Category::factory()->create(['slug' => 'restaurang-cafe', 'namn' => ['sv' => 'Restaurang & café', 'en' => 'Restaurants & cafés']]);
    $shop = Category::factory()->create(['slug' => 'butik']);
    $roma = Business::factory()->published()->create(['namn' => 'Pizzeria Roma']);
    $roma->categories()->attach($cafe);
    $bageri = Business::factory()->published()->create(['namn' => 'Bageriet']);
    $bageri->categories()->attach($cafe);
    $mode = Business::factory()->published()->create(['namn' => 'Roma Mode']);
    $mode->categories()->attach($shop);

    foreach ([$roma, $bageri, $mode] as $business) {
        BusinessLocation::factory()->for($business)->create(['kommun_id' => $kommun]);
    }

    $byCategory = $this->getJson("/api/v1/{$site->slug}/businesses?category=restaurang-cafe")->assertOk();

    expect($byCategory->json('data.*.namn'))->toBe(['Bageriet', 'Pizzeria Roma'])
        ->and($byCategory->json('data.0.categories.0'))->toMatchArray(['slug' => 'restaurang-cafe', 'namn' => 'Restaurang & café'])
        ->and($this->getJson("/api/v1/{$site->slug}/businesses?q=roma")->json('data.*.namn'))->toBe(['Pizzeria Roma', 'Roma Mode'])
        ->and($this->getJson("/api/v1/{$site->slug}/businesses?q=roma&category=butik")->json('data.*.namn'))->toBe(['Roma Mode'])
        ->and($this->getJson("/api/v1/{$site->slug}/businesses?q=%25")->json('data'))->toBe([]);
});

it('tells whether each location is open right now, with exceptions winning over regular hours', function () {
    $this->travelTo(Carbon::parse('2026-11-03 12:00', 'Europe/Stockholm')); // tisdag
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;
    $location = fn (string $namn): BusinessLocation => BusinessLocation::factory()
        ->for(Business::factory()->published()->create(['namn' => $namn]))
        ->create(['kommun_id' => $kommun]);

    $open = $location('Oppen');
    OpeningHour::factory()->for($open)->create(['weekday' => 1, 'opens' => '09:00', 'closes' => '17:00']);

    $lunchClosed = $location('Lunchstangt');
    OpeningHour::factory()->for($lunchClosed)->create(['weekday' => 1, 'opens' => '09:00', 'closes' => '11:00']);
    OpeningHour::factory()->for($lunchClosed)->create(['weekday' => 1, 'opens' => '13:00', 'closes' => '17:00']);

    $closedToday = $location('Stangd idag');
    OpeningHour::factory()->for($closedToday)->create(['weekday' => 1, 'opens' => '09:00', 'closes' => '17:00']);
    OpeningHourException::factory()->for($closedToday)->create(['date' => '2026-11-03']);

    $extraOpen = $location('Extraoppet');
    OpeningHourException::factory()->for($extraOpen)->open('10:00', '14:00')->create(['date' => '2026-11-03']);

    $location('Okanda tider');

    $response = $this->getJson("/api/v1/{$site->slug}/businesses")->assertOk();

    expect(collect($response->json('data'))->pluck('open_now', 'namn')->all())->toEqual([
        'Oppen' => true,
        'Lunchstangt' => false,
        'Stangd idag' => false,
        'Extraoppet' => true,
        'Okanda tider' => false,
    ]);
});

it('shows a location with this week\'s hours, the current lunch menu, images and upcoming events', function () {
    Storage::fake('public');
    $this->travelTo(Carbon::parse('2026-11-04 10:00', 'Europe/Stockholm')); // onsdag, ISO-vecka 45
    $site = Site::factory()->active()->create();
    $business = Business::factory()->published()->create([
        'namn' => 'Stadshotellet',
        'beskrivning' => ['sv' => 'Hotell och krog', 'en' => 'Hotel and pub'],
    ]);
    $business->addMedia(UploadedFile::fake()->image('fasad.jpg', 640, 480))->toMediaCollection(Business::MEDIA_IMAGES);
    $business->addMedia(UploadedFile::fake()->image('vantande.jpg', 10, 10))->toMediaCollection(Business::MEDIA_PENDING);
    $location = BusinessLocation::factory()->for($business)->create(['kommun_id' => $site->primary_kommun_id]);
    $location->setSlug('sv', 'stadshotellet');
    $location->setSlug('en', 'town-hotel');
    OpeningHour::factory()->for($location)->create(['weekday' => 0, 'opens' => '18:00', 'closes' => '22:00']);
    OpeningHour::factory()->for($location)->create(['weekday' => 0, 'opens' => '09:00', 'closes' => '17:00']);
    OpeningHourException::factory()->for($location)->create(['date' => '2026-11-06', 'note' => 'Semesterstängt']);
    LunchMenu::factory()->for($location)->create(['iso_year' => 2026, 'week' => 44, 'status' => ContentStatus::Published, 'menu' => ['wed' => ['Förra veckan']]]);
    LunchMenu::factory()->for($location)->create(['iso_year' => 2026, 'week' => 45, 'status' => ContentStatus::Published, 'menu' => ['wed' => ['Köttbullar', 'Fisksoppa']]]);
    LunchMenu::factory()->for($location)->create(['iso_year' => 2026, 'week' => 46, 'status' => ContentStatus::Published, 'menu' => ['wed' => ['Nästa vecka']]]);
    $upcoming = Event::factory()->published()->create(['business_location_id' => $location, 'starts_at' => now()->addDay()]);
    Event::factory()->published()->create(['business_location_id' => $location, 'starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHours(2)]);
    Event::factory()->published()->create(['business_location_id' => $location, 'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=TU', 'starts_at' => now()->addDays(3)]);
    Event::factory()->create(['business_location_id' => $location, 'starts_at' => now()->addDays(2)]);

    $response = $this->getJson("/api/v1/{$site->slug}/businesses/stadshotellet")->assertOk();

    expect($response->json('data.namn'))->toBe('Stadshotellet')
        ->and($response->json('data.slug'))->toBe('stadshotellet')
        ->and($response->json('data.slugs'))->toBe(['sv' => 'stadshotellet', 'en' => 'town-hotel'])
        ->and($response->json('data.business.beskrivning'))->toBe('Hotell och krog')
        ->and($response->json('data.opening_hours'))->toHaveCount(7)
        ->and($response->json('data.opening_hours.0'))->toEqual([
            'date' => '2026-11-02', 'weekday' => 0, 'closed' => false, 'is_exception' => false, 'note' => null,
            'intervals' => [['opens' => '09:00', 'closes' => '17:00'], ['opens' => '18:00', 'closes' => '22:00']],
        ])
        ->and($response->json('data.opening_hours.1'))->toMatchArray(['date' => '2026-11-03', 'closed' => true, 'intervals' => []])
        ->and($response->json('data.opening_hours.4'))->toEqual([
            'date' => '2026-11-06', 'weekday' => 4, 'closed' => true, 'is_exception' => true, 'note' => 'Semesterstängt', 'intervals' => [],
        ])
        ->and($response->json('data.lunch_menu.week'))->toBe(45)
        ->and($response->json('data.lunch_menu.today'))->toBe(['Köttbullar', 'Fisksoppa'])
        ->and($response->json('data.images'))->toHaveCount(1)
        ->and($response->json('data.images.0'))->toMatchArray(['width' => 640, 'height' => 480, 'mime_type' => 'image/jpeg', 'alt' => null])
        ->and($response->json('data.images.0.url'))->toEndWith('/fasad.jpg')
        ->and($response->json('data.upcoming_events.*.id'))->toBe([$upcoming->id]);
});

it('returns null for the lunch menu when there is none this week', function () {
    $site = Site::factory()->active()->create();
    $location = BusinessLocation::factory()->for(Business::factory()->published())->create(['kommun_id' => $site->primary_kommun_id]);

    $this->getJson("/api/v1/{$site->slug}/businesses/{$location->slugFor('sv')}")
        ->assertOk()
        ->assertJsonPath('data.lunch_menu', null)
        ->assertJsonPath('data.upcoming_events', []);
});

it('redirects an old slug to the canonical one with 301', function () {
    $site = Site::factory()->active()->create();
    $location = BusinessLocation::factory()->for(Business::factory()->published())->create(['kommun_id' => $site->primary_kommun_id]);
    $location->setSlug('sv', 'gamla-namnet');
    $location->setSlug('sv', 'nya-namnet');

    $this->getJson("/api/v1/{$site->slug}/businesses/gamla-namnet")
        ->assertStatus(301)
        ->assertExactJson(['redirect_to' => 'nya-namnet']);
    $this->getJson("/api/v1/{$site->slug}/businesses/nya-namnet")
        ->assertOk()
        ->assertJsonPath('data.slug', 'nya-namnet');
});

it('redirects to the slug of the requested locale', function () {
    $site = Site::factory()->active()->create();
    $location = BusinessLocation::factory()->for(Business::factory()->published())->create(['kommun_id' => $site->primary_kommun_id]);
    $location->setSlug('sv', 'bageriet');
    $location->setSlug('en', 'the-bakery');

    $this->getJson("/api/v1/{$site->slug}/businesses/bageriet?locale=en")
        ->assertStatus(301)
        ->assertExactJson(['redirect_to' => 'the-bakery']);
    $this->getJson("/api/v1/{$site->slug}/businesses/the-bakery?locale=en")
        ->assertOk()
        ->assertJsonPath('data.slug', 'the-bakery');
    $this->getJson("/api/v1/{$site->slug}/businesses/the-bakery")
        ->assertStatus(301)
        ->assertExactJson(['redirect_to' => 'bageriet']);
});

it('returns 404 for other kommuner, unpublished businesses and unknown slugs', function () {
    $site = Site::factory()->active()->create();
    $elsewhere = BusinessLocation::factory()->for(Business::factory()->published())->create();
    $draft = BusinessLocation::factory()->for(Business::factory())->create(['kommun_id' => $site->primary_kommun_id]);

    $this->getJson("/api/v1/{$site->slug}/businesses/{$elsewhere->slugFor('sv')}")->assertNotFound();
    $this->getJson("/api/v1/{$site->slug}/businesses/{$draft->slugFor('sv')}")->assertNotFound();
    $this->getJson("/api/v1/{$site->slug}/businesses/finns-inte")->assertNotFound();
});

it('rejects an invalid page size with 422', function () {
    $site = Site::factory()->active()->create();

    $this->getJson("/api/v1/{$site->slug}/businesses?per_page=500")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});

it('hides draft locations from the list and answers 404 for them', function () {
    $site = Site::factory()->active()->create();
    $business = Business::factory()->published()->create();
    $published = BusinessLocation::factory()->for($business)->create(['kommun_id' => $site->primary_kommun_id]);
    $draft = BusinessLocation::factory()->draft()->secondary()->for($business)->create(['kommun_id' => $site->primary_kommun_id]);

    $this->getJson("/api/v1/{$site->slug}/businesses")
        ->assertOk()
        ->assertJsonPath('data.*.id', [$published->id]);
    $this->getJson("/api/v1/{$site->slug}/businesses/{$draft->slugFor('sv')}")->assertNotFound();
    $this->getJson("/api/v1/{$site->slug}/businesses/{$published->slugFor('sv')}")->assertOk();
});

it('evaluates open-now in the display timezone across the switch to daylight saving time', function () {
    // Sista söndagen i mars 2027 hoppar klockan 02:00 → 03:00. "02:30 svensk tid" finns inte, så PHP
    // flyttar den till 03:30 CEST = 01:30 UTC. now() är UTC – klockslaget måste räknas i visningstidszonen.
    $this->travelTo(Carbon::parse('2027-03-28 02:30', 'Europe/Stockholm'));

    expect(now()->toIso8601String())->toBe('2027-03-28T01:30:00+00:00');

    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;
    $location = fn (string $namn): BusinessLocation => BusinessLocation::factory()
        ->for(Business::factory()->published()->create(['namn' => $namn]))
        ->create(['kommun_id' => $kommun]);

    OpeningHour::factory()->for($location('Sondag 03-06'))->create(['weekday' => 6, 'opens' => '03:00', 'closes' => '06:00']);
    OpeningHour::factory()->for($location('Lordagsnatt till 04'))->create(['weekday' => 5, 'opens' => '22:00', 'closes' => '04:00']);
    OpeningHour::factory()->for($location('Lordagsnatt till 03'))->create(['weekday' => 5, 'opens' => '22:00', 'closes' => '03:00']);
    OpeningHour::factory()->for($location('Sondag 01-02, oppet bara i UTC'))->create(['weekday' => 6, 'opens' => '01:00', 'closes' => '02:00']);

    $response = $this->getJson("/api/v1/{$site->slug}/businesses")->assertOk();

    expect(collect($response->json('data'))->pluck('open_now', 'namn')->all())->toEqual([
        'Sondag 03-06' => true,
        'Lordagsnatt till 04' => true,
        'Lordagsnatt till 03' => false,
        'Sondag 01-02, oppet bara i UTC' => false,
    ]);
});
