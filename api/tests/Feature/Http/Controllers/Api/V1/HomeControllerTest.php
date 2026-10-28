<?php

use App\Enums\ContentStatus;
use App\Enums\PromotionPlacement;
use App\Models\Article;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\LunchMenu;
use App\Models\Promotion;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('builds the start page in one call', function () {
    $this->travelTo(Carbon::parse('2026-11-04 10:00', 'Europe/Stockholm')); // onsdag
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;

    $featured = Article::factory()->count(4)->published()
        ->sequence(fn (Sequence $sequence) => ['published_at' => now()->subDays($sequence->index)])
        ->create(['kommun_id' => $kommun, 'featured' => true]);
    Article::factory()->published()->create(['kommun_id' => $kommun, 'featured' => false]);

    $events = Event::factory()->count(7)->published()
        ->sequence(fn (Sequence $sequence) => [
            'starts_at' => now()->addDays($sequence->index + 1),
            'ends_at' => now()->addDays($sequence->index + 1)->addHour(),
        ])
        ->create(['kommun_id' => $kommun]);

    $promotion = Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => $events->first()]);
    Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => $events->first(), 'placement' => PromotionPlacement::Kategorisida]);

    $krogen = BusinessLocation::factory()->for(Business::factory()->published()->create(['namn' => 'Krogen']))->create(['kommun_id' => $kommun]);
    LunchMenu::factory()->for($krogen)->create(['status' => ContentStatus::Published, 'menu' => ['wed' => ['Pannbiff'], 'thu' => ['Soppa']]]);
    $helgkrogen = BusinessLocation::factory()->for(Business::factory()->published()->create(['namn' => 'Helgkrogen']))->create(['kommun_id' => $kommun]);
    LunchMenu::factory()->for($helgkrogen)->create(['status' => ContentStatus::Published, 'menu' => ['sat' => ['Brunch']]]);
    $utkast = BusinessLocation::factory()->for(Business::factory()->published()->create(['namn' => 'Utkastkrogen']))->create(['kommun_id' => $kommun]);
    LunchMenu::factory()->for($utkast)->create(['menu' => ['wed' => ['Utkast']]]);
    $annanOrt = BusinessLocation::factory()->for(Business::factory()->published())->create();
    LunchMenu::factory()->for($annanOrt)->create(['status' => ContentStatus::Published, 'menu' => ['wed' => ['Annan ort']]]);

    $response = $this->getJson("/api/v1/{$site->slug}/home")->assertOk();

    expect($response->json('data.featured_articles.*.id'))->toBe($featured->take(3)->pluck('id')->all())
        ->and($response->json('data.upcoming_events.*.id'))->toBe($events->take(6)->pluck('id')->all())
        ->and($response->json('data.promotions.*.id'))->toBe([$promotion->id])
        ->and($response->json('data.lunch_menus'))->toHaveCount(1)
        ->and($response->json('data.lunch_menus.0.location.namn'))->toBe('Krogen')
        ->and($response->json('data.lunch_menus.0.today'))->toBe(['Pannbiff'])
        ->and($response->json('data'))->toHaveKey('weather')
        ->and($response->json('data.weather'))->toBeNull();
});
