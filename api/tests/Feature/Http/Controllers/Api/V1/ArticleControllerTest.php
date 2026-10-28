<?php

use App\Models\Article;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists published articles newest first without drafts, other kommuner or the body', function () {
    $this->freezeTime();
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;
    $older = Article::factory()->published()->create(['kommun_id' => $kommun, 'published_at' => now()->subDays(2)]);
    $newer = Article::factory()->published()->create(['kommun_id' => $kommun, 'published_at' => now()->subDay()]);
    Article::factory()->create(['kommun_id' => $kommun]);
    Article::factory()->published()->create();

    $response = $this->getJson("/api/v1/{$site->slug}/articles")->assertOk();

    expect($response->json('data.*.id'))->toBe([$newer->id, $older->id])
        ->and($response->json('data.0'))->not->toHaveKey('body');
});

it('puts featured articles first when asked', function () {
    $this->freezeTime();
    $site = Site::factory()->active()->create();
    $kommun = $site->primary_kommun_id;
    $featuredOld = Article::factory()->published()->create(['kommun_id' => $kommun, 'featured' => true, 'published_at' => now()->subDays(3)]);
    $plainNew = Article::factory()->published()->create(['kommun_id' => $kommun, 'featured' => false, 'published_at' => now()->subDay()]);

    expect($this->getJson("/api/v1/{$site->slug}/articles")->json('data.*.id'))->toBe([$plainNew->id, $featuredOld->id])
        ->and($this->getJson("/api/v1/{$site->slug}/articles?featured=1")->json('data.*.id'))->toBe([$featuredOld->id, $plainNew->id]);
});

it('shows an article with its body and canonical slugs per locale, and redirects old slugs with 301', function () {
    $site = Site::factory()->active()->create();
    $article = Article::factory()->published()->create([
        'kommun_id' => $site->primary_kommun_id,
        'titel' => ['sv' => 'Midsommar i Ludvika', 'en' => 'Midsummer in Ludvika'],
        'body' => ['sv' => '<p>Dans kring stången.</p>', 'en' => '<p>Dancing.</p>'],
        'author_name' => 'Anna Andersson',
    ]);
    $article->setSlug('sv', 'midsommar-2026');
    $article->setSlug('en', 'midsummer-2026');

    $this->getJson("/api/v1/{$site->slug}/articles/midsommar-2026")
        ->assertOk()
        ->assertJsonPath('data.titel', 'Midsommar i Ludvika')
        ->assertJsonPath('data.slug', 'midsommar-2026')
        ->assertJsonPath('data.slugs', ['sv' => 'midsommar-2026', 'en' => 'midsummer-2026'])
        ->assertJsonPath('data.body', '<p>Dans kring stången.</p>')
        ->assertJsonPath('data.author_name', 'Anna Andersson');
    $this->getJson("/api/v1/{$site->slug}/articles/midsommar-i-ludvika")
        ->assertStatus(301)
        ->assertExactJson(['redirect_to' => 'midsommar-2026']);
    $this->getJson("/api/v1/{$site->slug}/articles/finns-inte")->assertNotFound();
});
