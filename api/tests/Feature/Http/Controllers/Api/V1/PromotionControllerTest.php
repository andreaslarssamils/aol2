<?php

use App\Enums\ContentStatus;
use App\Enums\PromotionPlacement;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\Offer;
use App\Models\Promotion;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists active promotions with paid flag, content and a site-relative link, highest priority first', function () {
    $this->freezeTime();
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;
    $ludvika = Kommun::factory()->create(['slug' => 'ludvika']);
    Site::factory()->active()->create(['slug' => 'ludvika', 'primary_kommun_id' => $ludvika]);
    $event = Event::factory()->published()->create(['kommun_id' => $ludvika, 'namn' => 'Nyhammarsfesten']);

    $paid = Promotion::factory()->paid()->create(['kommun_id' => $kommun, 'promotable_id' => $event, 'priority' => 10]);
    $free = Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => $event, 'priority' => 1]);
    Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => $event, 'starts_at' => now()->addMinute()]);
    Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => $event, 'ends_at' => now()->subMinute()]);
    Promotion::factory()->paused()->create(['kommun_id' => $kommun, 'promotable_id' => $event]);
    Promotion::factory()->create(['kommun_id' => $ludvika, 'promotable_id' => $event]);
    Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => Event::factory()->create(['kommun_id' => $ludvika])]);
    $newsletter = Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => $event, 'placement' => PromotionPlacement::Nyhetsbrev]);

    $response = $this->getJson("/api/v1/{$site->slug}/promotions?placement=startsida")->assertOk();

    expect($response->json('data.*.id'))->toBe([$paid->id, $free->id])
        ->and($response->json('data.0'))->toMatchArray(['is_paid' => true, 'type' => 'event', 'placement' => 'startsida', 'priority' => 10])
        ->and($response->json('data.1.is_paid'))->toBeFalse()
        ->and($response->json('data.0.content.namn'))->toBe('Nyhammarsfesten')
        ->and($response->json('data.0.link'))->toBe([
            'type' => 'event',
            'slug' => 'nyhammarsfesten',
            'locale' => 'sv',
            'kommun' => 'ludvika',
            'site' => 'ludvika',
            'path' => '/evenemang/nyhammarsfesten',
        ])
        ->and($this->getJson("/api/v1/{$site->slug}/promotions")->json('data.*.id'))->toBe([$paid->id, $free->id, $newsletter->id]);
});

it('sends offers to the business primary location and never includes an origin in the link', function () {
    $site = Site::factory()->active()->create();
    $ludvika = Kommun::factory()->create(['slug' => 'ludvika']);
    Site::factory()->active()->create(['slug' => 'ludvika', 'domain' => 'alltomludvika.se', 'primary_kommun_id' => $ludvika]);
    $business = Business::factory()->published()->create(['namn' => 'Acme AB']);
    $location = BusinessLocation::factory()->for($business)->create(['kommun_id' => $ludvika]);
    $location->setSlug('sv', 'acme');
    $offer = Offer::factory()->for($business)->create(['status' => ContentStatus::Published, 'titel' => ['sv' => '20 % på allt', 'en' => '20% off']]);
    Promotion::factory()->create(['kommun_id' => $site->primary_kommun_id, 'promotable_type' => 'offer', 'promotable_id' => $offer]);

    $this->getJson("/api/v1/{$site->slug}/promotions")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'offer')
        ->assertJsonPath('data.0.content.titel', '20 % på allt')
        ->assertJsonPath('data.0.content.business.namn', 'Acme AB')
        ->assertJsonPath('data.0.link.type', 'business_location')
        ->assertJsonPath('data.0.link.path', '/foretag/acme')
        ->assertJsonPath('data.0.link.site', 'ludvika')
        ->assertJsonMissingPath('data.0.link.url');
});

it('rejects an unknown placement with 422', function () {
    $site = Site::factory()->active()->create();

    $this->getJson("/api/v1/{$site->slug}/promotions?placement=banner")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('placement');
});
