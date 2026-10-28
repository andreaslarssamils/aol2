<?php

use App\Enums\ContentStatus;
use App\Models\Article;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serves the cached list until an article is published or unpublished', function () {
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;
    $first = Article::factory()->published()->create(['kommun_id' => $kommun]);
    $url = "/api/v1/{$site->slug}/articles";

    $this->getJson($url)->assertHeader('X-Cache', 'MISS')->assertJsonCount(1, 'data');
    $this->getJson($url)->assertHeader('X-Cache', 'HIT')->assertJsonCount(1, 'data');

    // Ett utkast som sparas rör inte det publika – cachen ligger kvar.
    $draft = Article::factory()->create(['kommun_id' => $kommun]);

    $this->getJson($url)->assertHeader('X-Cache', 'HIT')->assertJsonCount(1, 'data');

    $draft->update(['status' => ContentStatus::Published, 'published_at' => now()]);

    $this->getJson($url)->assertHeader('X-Cache', 'MISS')->assertJsonCount(2, 'data');
    $this->getJson($url)->assertHeader('X-Cache', 'HIT');

    $first->update(['status' => ContentStatus::Archived]);

    $this->getJson($url)->assertHeader('X-Cache', 'MISS')->assertJsonCount(1, 'data');
});

it('caches per locale and per site', function () {
    $site = Site::factory()->active()->create();
    $otherSite = Site::factory()->active()->create();
    Article::factory()->published()->create([
        'kommun_id' => $site->primary_kommun_id,
        'titel' => ['sv' => 'Midsommar', 'en' => 'Midsummer'],
    ]);

    $this->getJson("/api/v1/{$site->slug}/articles")->assertHeader('X-Cache', 'MISS')->assertJsonPath('data.0.titel', 'Midsommar');
    $this->getJson("/api/v1/{$site->slug}/articles?locale=en")->assertHeader('X-Cache', 'MISS')->assertJsonPath('data.0.titel', 'Midsummer');
    $this->getJson("/api/v1/{$site->slug}/articles")->assertHeader('X-Cache', 'HIT')->assertJsonPath('data.0.titel', 'Midsommar');
    $this->getJson("/api/v1/{$otherSite->slug}/articles")->assertHeader('X-Cache', 'MISS')->assertJsonCount(0, 'data');
});

it('flushes the detail response when the slug is renamed', function () {
    $site = Site::factory()->active()->create();
    $article = Article::factory()->published()->create(['kommun_id' => $site->primary_kommun_id]);
    $url = "/api/v1/{$site->slug}/articles/{$article->slugFor('sv')}";

    $this->getJson($url)->assertOk()->assertHeader('X-Cache', 'MISS');
    $this->getJson($url)->assertOk()->assertHeader('X-Cache', 'HIT');

    $article->setSlug('sv', 'nytt-namn');

    $this->getJson($url)->assertStatus(301)->assertExactJson(['redirect_to' => 'nytt-namn']);
});

it('answers 304 when the ETag matches', function () {
    $site = Site::factory()->active()->create();
    $url = "/api/v1/{$site->slug}/site";

    $etag = $this->getJson($url)->assertOk()->headers->get('ETag');

    expect($etag)->not->toBeNull();

    $this->getJson($url, ['If-None-Match' => $etag])->assertStatus(304);
});

it('is bypassed on a cache store without tag support', function () {
    config()->set('api.cache.store', 'database');
    $site = Site::factory()->active()->create();
    $url = "/api/v1/{$site->slug}/site";

    $this->getJson($url)->assertOk()->assertHeaderMissing('X-Cache');
    $this->getJson($url)->assertOk()->assertHeaderMissing('X-Cache');
});
