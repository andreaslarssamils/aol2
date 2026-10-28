<?php

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Event;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('lists upcoming occurrences and single events, not templates, drafts, past events or other kommuner', function () {
    $this->freezeTime();
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;

    $template = Event::factory()->published()->create(['kommun_id' => $kommun, 'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=TU', 'starts_at' => now()->addDay()]);
    $occurrence = Event::factory()->published()->create(['kommun_id' => $kommun, 'parent_event_id' => $template, 'starts_at' => now()->addDays(2)]);
    $single = Event::factory()->published()->create(['kommun_id' => $kommun, 'starts_at' => now()->addDay()->addHour()]);
    $ongoing = Event::factory()->published()->create(['kommun_id' => $kommun, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()]);
    Event::factory()->published()->create(['kommun_id' => $kommun, 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDays(2)->addHours(2)]);
    Event::factory()->create(['kommun_id' => $kommun, 'starts_at' => now()->addDay()]);
    Event::factory()->published()->create(['starts_at' => now()->addDay()]);

    $response = $this->getJson("/api/v1/{$site->slug}/events")->assertOk();

    expect($response->json('data.*.id'))->toBe([$ongoing->id, $single->id, $occurrence->id])
        ->and($response->json('meta.total'))->toBe(3);
});

it('filters on a date range and category', function () {
    $this->travelTo(Carbon::parse('2026-11-01 08:00', 'UTC'));
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;
    $musik = Category::factory()->event()->create(['slug' => 'musik']);

    Event::factory()->published()->create(['kommun_id' => $kommun, 'starts_at' => '2026-11-05 19:00:00', 'ends_at' => '2026-11-05 21:00:00']);
    $december = Event::factory()->published()->create(['kommun_id' => $kommun, 'starts_at' => '2026-12-10 19:00:00', 'ends_at' => '2026-12-10 21:00:00']);
    $december->categories()->attach($musik);
    $newYearsEve = Event::factory()->published()->create(['kommun_id' => $kommun, 'starts_at' => '2026-12-31 20:00:00', 'ends_at' => '2027-01-01 01:00:00']);
    $january = Event::factory()->published()->create(['kommun_id' => $kommun, 'starts_at' => '2027-01-05 19:00:00', 'ends_at' => '2027-01-05 21:00:00']);

    expect($this->getJson("/api/v1/{$site->slug}/events?from=2026-12-01&to=2026-12-31")->json('data.*.id'))->toBe([$december->id, $newYearsEve->id])
        ->and($this->getJson("/api/v1/{$site->slug}/events?from=2026-12-11")->json('data.*.id'))->toBe([$newYearsEve->id, $january->id])
        ->and($this->getJson("/api/v1/{$site->slug}/events?category=musik")->json('data.*.id'))->toBe([$december->id]);

    $this->getJson("/api/v1/{$site->slug}/events?from=igår")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('from');
});

it('shows an event with kommun, categories, venue and canonical slugs per locale, and redirects old slugs with 301', function () {
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;
    $location = BusinessLocation::factory()->for(Business::factory()->published()->create(['namn' => 'Stadshotellet']))->create(['kommun_id' => $kommun]);
    $event = Event::factory()->published()->create([
        'kommun_id' => $kommun,
        'business_location_id' => $location,
        'namn' => 'Pubquiz',
        'starts_at' => '2026-11-03 18:00:00', // UTC i databasen
        'ends_at' => '2026-11-03 20:00:00',
    ]);
    $event->categories()->attach(Category::factory()->event()->create(['slug' => 'musik', 'namn' => ['sv' => 'Musik', 'en' => 'Music']]));
    $event->setSlug('sv', 'pubquiz-tisdagar');
    $event->setSlug('en', 'pub-quiz-tuesdays');

    $this->getJson("/api/v1/{$site->slug}/events/pubquiz-tisdagar")
        ->assertOk()
        ->assertJsonPath('data.namn', 'Pubquiz')
        ->assertJsonPath('data.slug', 'pubquiz-tisdagar')
        ->assertJsonPath('data.slugs', ['sv' => 'pubquiz-tisdagar', 'en' => 'pub-quiz-tuesdays'])
        ->assertJsonPath('data.starts_at', '2026-11-03T19:00:00+01:00')
        ->assertJsonPath('data.ends_at', '2026-11-03T21:00:00+01:00')
        ->assertJsonPath('data.kommun.id', $kommun)
        ->assertJsonPath('data.categories.0.namn', 'Musik')
        ->assertJsonPath('data.location', ['slug' => $location->slugFor('sv'), 'namn' => 'Stadshotellet']);
    $this->getJson("/api/v1/{$site->slug}/events/pubquiz")
        ->assertStatus(301)
        ->assertExactJson(['redirect_to' => 'pubquiz-tisdagar']);
});

it('does not serve recurrence templates or drafts by slug', function () {
    $site = Site::factory()->active()->create();
    $template = Event::factory()->published()->create(['kommun_id' => $site->primary_kommun_id, 'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=TU']);
    $draft = Event::factory()->create(['kommun_id' => $site->primary_kommun_id]);

    $this->getJson("/api/v1/{$site->slug}/events/{$template->slugFor('sv')}")->assertNotFound();
    $this->getJson("/api/v1/{$site->slug}/events/{$draft->slugFor('sv')}")->assertNotFound();
});
