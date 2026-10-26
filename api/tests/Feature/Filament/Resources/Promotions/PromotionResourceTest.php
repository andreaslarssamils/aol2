<?php

use App\Enums\PromotionPlacement;
use App\Enums\PromotionStatus;
use App\Filament\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Resources\Promotions\Pages\EditPromotion;
use App\Filament\Resources\Promotions\Pages\ListPromotions;
use App\Filament\Resources\Promotions\PromotionResource;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists promotions for the editorial team', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $promotions = Promotion::factory()->count(2)->create();

    $this->get(PromotionResource::getUrl('index'))->assertOk();

    Livewire::test(ListPromotions::class)
        ->assertCanSeeTableRecords($promotions);
});

it('filters the list on kommun and status', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $ludvika = Kommun::factory()->create();
    $active = Promotion::factory()->create(['kommun_id' => $ludvika]);
    $paused = Promotion::factory()->paused()->create(['kommun_id' => $ludvika]);
    $elsewhere = Promotion::factory()->create();

    Livewire::test(ListPromotions::class)
        ->filterTable('kommun_id', $ludvika->id)
        ->assertCanSeeTableRecords([$active, $paused])
        ->assertCanNotSeeTableRecords([$elsewhere])
        ->filterTable('status', PromotionStatus::Paused->value)
        ->assertCanSeeTableRecords([$paused])
        ->assertCanNotSeeTableRecords([$active, $elsewhere]);
});

it('creates a paid promotion of an event in another kommun', function () {
    $editor = User::factory()->redaktor()->create();
    $this->actingAs($editor);
    $event = Event::factory()->create();
    $smedjebacken = Kommun::factory()->create();

    Livewire::test(CreatePromotion::class)
        ->fillForm([
            'promotable_type' => $event->getMorphClass(),
            'promotable_id' => $event->id,
            'kommun_id' => $smedjebacken->id,
            'placement' => PromotionPlacement::Startsida->value,
            'status' => PromotionStatus::Active->value,
            'priority' => 5,
            'starts_at' => '2026-11-01 00:00:00',
            'ends_at' => '2026-11-30 23:59:00',
            'is_paid' => true,
            'price' => 1500,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $promotion = Promotion::sole();

    expect($promotion->promotable->is($event))->toBeTrue()
        ->and($promotion->promotable_type)->toBe('event')
        ->and($promotion->kommun_id)->toBe($smedjebacken->id)
        ->and($promotion->status)->toBe(PromotionStatus::Active)
        ->and($promotion->is_paid)->toBeTrue()
        ->and((float) $promotion->price)->toBe(1500.0)
        ->and($promotion->created_by)->toBe($editor->id);
});

it('rejects an end date before the start date', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $event = Event::factory()->create();

    Livewire::test(CreatePromotion::class)
        ->fillForm([
            'promotable_type' => $event->getMorphClass(),
            'promotable_id' => $event->id,
            'kommun_id' => Kommun::factory()->create()->id,
            'placement' => PromotionPlacement::Startsida->value,
            'starts_at' => '2026-11-10 00:00:00',
            'ends_at' => '2026-11-01 00:00:00',
        ])
        ->call('create')
        ->assertHasFormErrors(['ends_at']);

    expect(Promotion::count())->toBe(0);
});

it('pauses a promotion without touching its dates', function () {
    $this->actingAs(User::factory()->admin()->create());
    $promotion = Promotion::factory()->create([
        'starts_at' => '2026-11-01 08:00:00',
        'ends_at' => '2026-11-30 20:00:00',
    ]);

    Livewire::test(EditPromotion::class, ['record' => $promotion->getRouteKey()])
        ->fillForm(['status' => PromotionStatus::Paused->value, 'priority' => 9])
        ->call('save')
        ->assertHasNoFormErrors();

    $promotion->refresh();

    expect($promotion->status)->toBe(PromotionStatus::Paused)
        ->and($promotion->priority)->toBe(9)
        ->and($promotion->starts_at->toDateTimeString())->toBe('2026-11-01 08:00:00')
        ->and($promotion->ends_at->toDateTimeString())->toBe('2026-11-30 20:00:00');
});

it('forbids users without an editorial role', function (User $user) {
    $this->actingAs($user)
        ->get(PromotionResource::getUrl('index'))
        ->assertForbidden();
})->with([
    'no role' => fn () => User::factory()->create(),
    'verksamhetsägare' => fn () => User::factory()->verksamhetsagare()->create(),
]);
