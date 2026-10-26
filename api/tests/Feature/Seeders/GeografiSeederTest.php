<?php

use App\Models\Kommun;
use App\Models\Lan;
use Database\Seeders\KommunSeeder;
use Database\Seeders\LanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds all Swedish län and kommuner from SCB', function () {
    $this->seed([LanSeeder::class, KommunSeeder::class]);

    expect(Lan::count())->toBe(21)
        ->and(Kommun::count())->toBe(290);

    $ludvika = Kommun::where('kod', '2085')->first();

    expect($ludvika->namn)->toBe('Ludvika')
        ->and($ludvika->slug)->toBe('ludvika')
        ->and($ludvika->lan->kod)->toBe('20')
        ->and($ludvika->lan->namn)->toBe('Dalarnas län')
        ->and((float) $ludvika->lat)->toBeGreaterThan(60.0);
});

it('is idempotent', function () {
    $this->seed([LanSeeder::class, KommunSeeder::class]);
    $this->seed([LanSeeder::class, KommunSeeder::class]);

    expect(Kommun::count())->toBe(290);
});

it('gives colliding kommun names a län suffix', function () {
    $this->seed([LanSeeder::class, KommunSeeder::class]);

    expect(Kommun::where('kod', '0305')->value('slug'))->toBe('habo-uppsala')
        ->and(Kommun::where('kod', '0643')->value('slug'))->toBe('habo-jonkopings')
        ->and(Kommun::where('slug', 'habo')->exists())->toBeFalse();
});
