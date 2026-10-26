<?php

use App\Enums\PromotionPlacement;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists active promotions for a kommun and placement', function () {
    $kommun = Kommun::factory()->create();
    $event = Event::factory()->create();

    $active = Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => $event]);
    Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => $event, 'starts_at' => now()->addDay()]);
    Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => $event, 'ends_at' => now()->subDay()]);
    Promotion::factory()->create(['kommun_id' => $kommun, 'promotable_id' => $event, 'placement' => PromotionPlacement::Nyhetsbrev]);
    Promotion::factory()->paused()->create(['kommun_id' => $kommun, 'promotable_id' => $event]);

    $result = Promotion::active(PromotionPlacement::Startsida)->where('kommun_id', $kommun->id)->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->is($active))->toBeTrue()
        ->and($result->first()->promotable->is($event))->toBeTrue()
        ->and($event->promotions)->toHaveCount(5);
});
