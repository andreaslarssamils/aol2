<?php

use App\Models\Kommun;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a kommun override a national page with the same slug', function () {
    $ludvika = Kommun::factory()->create();
    $falun = Kommun::factory()->create();

    $national = Page::factory()->create(['kommun_id' => null]);
    $national->setSlug('sv', 'om-oss');
    $local = Page::factory()->create(['kommun_id' => $ludvika]);
    $local->setSlug('sv', 'om-oss');

    expect(Page::forKommun($ludvika, 'om-oss')->first()->is($local))->toBeTrue()
        ->and(Page::forKommun($falun, 'om-oss')->first()->is($national))->toBeTrue()
        ->and(Page::forKommun($falun, 'om-oss', 'en')->exists())->toBeFalse();
});
