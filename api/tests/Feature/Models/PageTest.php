<?php

use App\Models\Kommun;
use App\Models\Page;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('keeps national slugs unique even though kommun_id is null', function () {
    Page::factory()->create(['slug' => 'om-oss', 'kommun_id' => null]);

    expect(fn () => DB::transaction(fn () => Page::factory()->create(['slug' => 'om-oss', 'kommun_id' => null])))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('lets a kommun override a national page with the same slug', function () {
    $kommun = Kommun::factory()->create();
    $national = Page::factory()->create(['slug' => 'om-oss', 'kommun_id' => null]);
    $local = Page::factory()->create(['slug' => 'om-oss', 'kommun_id' => $kommun]);

    expect(Page::forKommun($kommun, 'om-oss')->first()->is($local))->toBeTrue()
        ->and(Page::forKommun(Kommun::factory()->create(), 'om-oss')->first()->is($national))->toBeTrue();
});
