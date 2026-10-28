<?php

use App\Models\Page;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prefers the kommun page over the national one with the same slug', function () {
    $site = Site::factory()->active()->create();
    $otherSite = Site::factory()->active()->create();
    $national = Page::factory()->published()->create(['kommun_id' => null, 'titel' => ['sv' => 'Om Allt om', 'en' => 'About']]);
    $national->setSlug('sv', 'om-oss');
    $local = Page::factory()->published()->create(['kommun_id' => $site->primary_kommun_id, 'titel' => ['sv' => 'Om Allt om Ludvika', 'en' => null]]);
    $local->setSlug('sv', 'om-oss');

    $this->getJson("/api/v1/{$site->slug}/pages/om-oss")
        ->assertOk()
        ->assertJsonPath('data.id', $local->id)
        ->assertJsonPath('data.titel', 'Om Allt om Ludvika')
        ->assertJsonPath('data.slugs', ['sv' => 'om-oss'])
        ->assertJsonPath('data.kommun.id', $site->primary_kommun_id);
    $this->getJson("/api/v1/{$otherSite->slug}/pages/om-oss")
        ->assertOk()
        ->assertJsonPath('data.id', $national->id)
        ->assertJsonPath('data.kommun', null);
});

it('hides drafts, pages of other kommuner and unknown slugs', function () {
    $site = Site::factory()->active()->create();
    $draft = Page::factory()->create(['kommun_id' => null]);
    $elsewhere = Page::factory()->published()->create(['kommun_id' => Site::factory()->active()->create()->primary_kommun_id]);

    $this->getJson("/api/v1/{$site->slug}/pages/{$draft->slugFor('sv')}")->assertNotFound();
    $this->getJson("/api/v1/{$site->slug}/pages/{$elsewhere->slugFor('sv')}")->assertNotFound();
    $this->getJson("/api/v1/{$site->slug}/pages/finns-inte")->assertNotFound();
});

it('redirects old slugs with 301', function () {
    $site = Site::factory()->active()->create();
    $page = Page::factory()->published()->create(['kommun_id' => null]);
    $page->setSlug('sv', 'integritetspolicy');
    $page->setSlug('sv', 'personuppgifter');

    $this->getJson("/api/v1/{$site->slug}/pages/integritetspolicy")
        ->assertStatus(301)
        ->assertExactJson(['redirect_to' => 'personuppgifter']);
});
