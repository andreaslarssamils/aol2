<?php

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 404 for sites that are not active', function (SiteStatus $status) {
    $site = Site::factory()->create(['status' => $status]);

    $this->getJson("/api/v1/{$site->slug}/site")->assertNotFound();
})->with([
    'hidden' => SiteStatus::Hidden,
    'coming soon' => SiteStatus::ComingSoon,
]);

it('returns 404 for an unknown site slug', function () {
    $this->getJson('/api/v1/finns-inte/site')->assertNotFound();
});

it('serves active sites', function () {
    $site = Site::factory()->active()->create();

    $this->getJson("/api/v1/{$site->slug}/site")
        ->assertOk()
        ->assertJsonPath('data.slug', $site->slug);
});
