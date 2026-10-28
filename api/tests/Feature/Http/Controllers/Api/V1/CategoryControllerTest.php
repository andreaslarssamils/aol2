<?php

use App\Enums\ContentStatus;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists only categories with published content on the site, sorted by type and sort order', function () {
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;

    $cafe = Category::factory()->create(['slug' => 'restaurang-cafe', 'namn' => ['sv' => 'Restaurang & café', 'en' => 'Restaurants & cafés'], 'sort_order' => 1]);
    $shop = Category::factory()->create(['slug' => 'butik', 'sort_order' => 0]);
    $draftOnly = Category::factory()->create(['slug' => 'bara-utkast']);
    $elsewhere = Category::factory()->create(['slug' => 'annan-kommun']);
    Category::factory()->create(['slug' => 'utan-innehall']);
    $music = Category::factory()->event()->create(['slug' => 'musik']);
    $pastOnly = Category::factory()->event()->create(['slug' => 'bara-passerat']);
    $templateOnly = Category::factory()->event()->create(['slug' => 'bara-mall']);
    $draftEventOnly = Category::factory()->event()->create(['slug' => 'bara-utkast-event']);

    $business = Business::factory()->published()->create();
    $business->categories()->attach([$cafe->id, $shop->id]);
    BusinessLocation::factory()->for($business)->create(['kommun_id' => $kommun]);

    $draftBusiness = Business::factory()->create();
    $draftBusiness->categories()->attach($draftOnly);
    BusinessLocation::factory()->for($draftBusiness)->create(['kommun_id' => $kommun]);

    $withDraftLocation = Business::factory()->published()->create();
    $withDraftLocation->categories()->attach($draftOnly);
    BusinessLocation::factory()->for($withDraftLocation)->draft()->create(['kommun_id' => $kommun]);

    $otherKommun = Business::factory()->published()->create();
    $otherKommun->categories()->attach($elsewhere);
    BusinessLocation::factory()->for($otherKommun)->create(['kommun_id' => Kommun::factory()]);

    Event::factory()->published()->create(['kommun_id' => $kommun])->categories()->attach($music);
    Event::factory()->published()->create(['kommun_id' => $kommun, 'starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHour()])->categories()->attach($pastOnly);
    Event::factory()->published()->create(['kommun_id' => $kommun, 'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=TU'])->categories()->attach($templateOnly);
    Event::factory()->create(['kommun_id' => $kommun])->categories()->attach($draftEventOnly);

    $response = $this->getJson("/api/v1/{$site->slug}/categories")->assertOk();

    expect($response->json('data.*.slug'))->toBe(['butik', 'restaurang-cafe', 'musik'])
        ->and($response->json('data.1'))->toMatchArray(['slug' => 'restaurang-cafe', 'namn' => 'Restaurang & café', 'type' => 'business'])
        ->and($this->getJson("/api/v1/{$site->slug}/categories?type=business")->json('data.*.slug'))->toBe(['butik', 'restaurang-cafe'])
        ->and($this->getJson("/api/v1/{$site->slug}/categories?type=event")->json('data.*.slug'))->toBe(['musik'])
        ->and($this->getJson("/api/v1/{$site->slug}/categories?locale=en")->json('data.1.namn'))->toBe('Restaurants & cafés');
});

it('rejects an unknown type with 422', function () {
    $site = Site::factory()->active()->create();

    $this->getJson("/api/v1/{$site->slug}/categories?type=sport")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('type');
});

it('is cached per site and flushed when a business is published', function () {
    $site = Site::factory()->active()->create();
    $category = Category::factory()->create(['slug' => 'butik']);
    $business = Business::factory()->create();
    $business->categories()->attach($category);
    BusinessLocation::factory()->for($business)->create(['kommun_id' => $site->primary_kommun_id]);
    $url = "/api/v1/{$site->slug}/categories";

    $this->getJson($url)->assertHeader('X-Cache', 'MISS')->assertJsonCount(0, 'data');
    $this->getJson($url)->assertHeader('X-Cache', 'HIT');

    $business->update(['status' => ContentStatus::Published, 'published_at' => now()]);

    $this->getJson($url)->assertHeader('X-Cache', 'MISS')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'butik');
});
