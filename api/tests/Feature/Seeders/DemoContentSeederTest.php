<?php

use App\Models\Business;
use App\Models\Event;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->seed([DatabaseSeeder::class, DemoContentSeeder::class]);
});

it('fills the Ludvika site with content for every frontend route', function () {
    $home = $this->getJson('/api/v1/ludvika/home')->assertOk();

    expect($home->json('data.featured_articles'))->toHaveCount(3)
        ->and($home->json('data.upcoming_events'))->toHaveCount(6)
        ->and(collect($home->json('data.promotions'))->pluck('is_paid')->all())->toContain(true, false)
        ->and($home->json('data.promotions.*.type'))->toContain('event', 'offer', 'article', 'business_location')
        ->and($home->json('data.lunch_menus.*.location.slug'))->toContain('stadshotellet');

    expect($this->getJson('/api/v1/ludvika/businesses')->assertOk()->json('meta.total'))->toBe(8)
        ->and($this->getJson('/api/v1/ludvika/businesses?category=restaurang-cafe')->json('data.*.slug'))->toBe(['cafe-lyktan', 'stadshotellet'])
        ->and($this->getJson('/api/v1/ludvika/events')->assertOk()->json('meta.total'))->toBe(8)
        ->and($this->getJson('/api/v1/ludvika/articles')->assertOk()->json('meta.total'))->toBe(5);

    $detail = $this->getJson('/api/v1/ludvika/businesses/stadshotellet')->assertOk();

    expect($detail->json('data.opening_hours'))->toHaveCount(7)
        ->and(collect($detail->json('data.opening_hours'))->where('is_exception', true))->toHaveCount(1)
        ->and($detail->json('data.lunch_menu.today'))->not->toBeEmpty()
        ->and($detail->json('data.images'))->toHaveCount(1)
        ->and($detail->json('data.images.0'))->toMatchArray(['width' => 1200, 'height' => 800, 'alt' => 'Stadshotellets fasad mot Storgatan'])
        ->and($detail->json('data.upcoming_events.*.namn'))->toContain('Pubquiz på Stadshotellet');

    $this->getJson('/api/v1/ludvika/businesses/stadshotellet-ludvika')->assertStatus(301)->assertJson(['redirect_to' => 'stadshotellet']);
    $this->getJson('/api/v1/ludvika/businesses/stadshotellet?locale=en')->assertStatus(301)->assertJson(['redirect_to' => 'town-hotel']);
    $this->getJson('/api/v1/ludvika/events/pubquiz-pa-stadshotellet')->assertOk()->assertJsonPath('data.location.slug', 'stadshotellet');
    $this->getJson('/api/v1/ludvika/articles/fem-vandringar-runt-vasman?locale=en')->assertStatus(301)->assertJson(['redirect_to' => 'five-hikes-around-lake-vasman']);
    $this->getJson('/api/v1/ludvika/pages/om-oss')->assertOk()->assertJsonPath('data.titel', 'Om Allt om Ludvika');
    $this->getJson('/api/v1/ludvika/pages/integritetspolicy')->assertOk()->assertJsonPath('data.kommun', null);
});

it('is idempotent and moves relative dates forward when re-run', function () {
    $this->travel(10)->days();
    $this->seed(DemoContentSeeder::class);

    expect(Business::count())->toBe(8)
        ->and(Event::count())->toBe(10)
        ->and($this->getJson('/api/v1/ludvika/events')->json('meta.total'))->toBe(8)
        ->and($this->getJson('/api/v1/ludvika/businesses/stadshotellet')->json('data.lunch_menu.today'))->not->toBeEmpty()
        ->and($this->getJson('/api/v1/ludvika/businesses/stadshotellet-ludvika')->assertStatus(301)->json('redirect_to'))->toBe('stadshotellet')
        ->and($this->getJson('/api/v1/ludvika/businesses/stadshotellet')->json('data.images'))->toHaveCount(1);
});
