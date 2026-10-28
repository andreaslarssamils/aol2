<?php

use App\Models\Article;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses ?locale= before Accept-Language', function () {
    $site = Site::factory()->active()->create();
    $article = Article::factory()->published()->create([
        'kommun_id' => $site->primary_kommun_id,
        'titel' => ['sv' => 'Midsommar i Ludvika', 'en' => 'Midsummer in Ludvika'],
    ]);
    $article->setSlug('en', 'midsummer-in-ludvika');

    $this->getJson("/api/v1/{$site->slug}/articles?locale=en", ['Accept-Language' => 'sv'])
        ->assertOk()
        ->assertJsonPath('data.0.titel', 'Midsummer in Ludvika')
        ->assertJsonPath('data.0.slug', 'midsummer-in-ludvika');
});

it('reads the locale from Accept-Language', function () {
    $site = Site::factory()->active()->create();
    $article = Article::factory()->published()->create([
        'kommun_id' => $site->primary_kommun_id,
        'titel' => ['sv' => 'Midsommar i Ludvika', 'en' => 'Midsummer in Ludvika'],
    ]);
    $article->setSlug('en', 'midsummer-in-ludvika');

    $this->getJson("/api/v1/{$site->slug}/articles", ['Accept-Language' => 'en-US,en;q=0.9,sv;q=0.8'])
        ->assertOk()
        ->assertJsonPath('data.0.titel', 'Midsummer in Ludvika')
        ->assertJsonPath('data.0.slug', 'midsummer-in-ludvika');
});

it('falls back to Swedish for unsupported locales', function () {
    $site = Site::factory()->active()->create();
    $article = Article::factory()->published()->create([
        'kommun_id' => $site->primary_kommun_id,
        'titel' => ['sv' => 'Midsommar i Ludvika', 'en' => 'Midsummer in Ludvika'],
    ]);
    $article->setSlug('en', 'midsummer-in-ludvika');

    $this->getJson("/api/v1/{$site->slug}/articles?locale=de", ['Accept-Language' => 'de'])
        ->assertOk()
        ->assertJsonPath('data.0.titel', 'Midsommar i Ludvika')
        ->assertJsonPath('data.0.slug', 'midsommar-i-ludvika');
});

it('falls back to the Swedish text and slug when the translation is missing', function () {
    $site = Site::factory()->active()->create();
    Article::factory()->published()->create([
        'kommun_id' => $site->primary_kommun_id,
        'titel' => ['sv' => 'Bara på svenska', 'en' => null],
        'ingress' => ['sv' => 'Ingress', 'en' => ''],
    ]);

    $this->getJson("/api/v1/{$site->slug}/articles?locale=en")
        ->assertOk()
        ->assertJsonPath('data.0.titel', 'Bara på svenska')
        ->assertJsonPath('data.0.ingress', 'Ingress')
        ->assertJsonPath('data.0.slug', 'bara-pa-svenska');
});

it('varies the response on Accept-Language', function () {
    $site = Site::factory()->active()->create();

    $this->getJson("/api/v1/{$site->slug}/site")
        ->assertOk()
        ->assertHeader('Vary', 'Accept-Language');
});
