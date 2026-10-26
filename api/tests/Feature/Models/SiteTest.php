<?php

use App\Models\Kommun;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can cover several kommuner with one primary', function () {
    $ludvika = Kommun::factory()->create(['namn' => 'Ludvika']);
    $smedjebacken = Kommun::factory()->create(['namn' => 'Smedjebacken']);

    $site = Site::factory()->create([
        'namn' => 'Allt om Västerbergslagen',
        'slug' => 'vasterbergslagen',
        'primary_kommun_id' => $ludvika,
    ]);
    $site->kommuner()->syncWithoutDetaching([$smedjebacken->id => ['sort_order' => 1]]);

    expect($site->kommuner)->toHaveCount(2)
        ->and($site->kommuner->first()->is($ludvika))->toBeTrue()
        ->and($site->primaryKommun->is($ludvika))->toBeTrue()
        ->and($site->kommunIds())->toBe([$ludvika->id, $smedjebacken->id])
        ->and($smedjebacken->sites->first()->is($site))->toBeTrue();
});
