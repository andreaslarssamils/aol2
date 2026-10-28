<?php

use App\Models\Kommun;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the site with its kommuner, primary kommun first, and settings', function () {
    $ludvika = Kommun::factory()->create(['namn' => 'Ludvika', 'slug' => 'ludvika']);
    $smedjebacken = Kommun::factory()->create(['namn' => 'Smedjebacken', 'slug' => 'smedjebacken']);
    $site = Site::factory()->active()->create([
        'namn' => 'Allt om Västerbergslagen',
        'slug' => 'vasterbergslagen',
        'primary_kommun_id' => $ludvika,
        'settings' => ['tagline' => 'Nära dig'],
    ]);
    $site->kommuner()->syncWithoutDetaching([$smedjebacken->id => ['sort_order' => 1]]);

    $this->getJson('/api/v1/vasterbergslagen/site')
        ->assertOk()
        ->assertJsonPath('data.namn', 'Allt om Västerbergslagen')
        ->assertJsonPath('data.domain', null)
        ->assertJsonPath('data.primary_kommun.slug', 'ludvika')
        ->assertJsonPath('data.kommuner.*.slug', ['ludvika', 'smedjebacken'])
        ->assertJsonPath('data.settings.tagline', 'Nära dig');
});

it('returns an empty object when the site has no settings', function () {
    $site = Site::factory()->active()->create(['settings' => null]);

    $response = $this->getJson("/api/v1/{$site->slug}/site")->assertOk();

    expect($response->content())->toContain('"settings":{}');
});
