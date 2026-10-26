<?php

use App\Filament\Resources\Kommuner\KommunResource;
use App\Filament\Resources\Kommuner\Pages\ManageKommuner;
use App\Models\Kommun;
use App\Models\Lan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists kommuner alphabetically for the editorial team', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $smedjebacken = Kommun::factory()->create(['namn' => 'Smedjebacken']);
    $ludvika = Kommun::factory()->create(['namn' => 'Ludvika']);

    $this->get(KommunResource::getUrl('index'))->assertOk();

    Livewire::test(ManageKommuner::class)
        ->assertCanSeeTableRecords([$ludvika, $smedjebacken], inOrder: true);
});

it('filters the list on län', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $dalarna = Lan::factory()->create();
    $ludvika = Kommun::factory()->create(['lan_id' => $dalarna]);
    $other = Kommun::factory()->create();

    Livewire::test(ManageKommuner::class)
        ->filterTable('lan_id', $dalarna->id)
        ->assertCanSeeTableRecords([$ludvika])
        ->assertCanNotSeeTableRecords([$other]);
});

it('is read-only: offers neither create nor edit or delete', function () {
    $this->actingAs(User::factory()->admin()->create());
    $kommun = Kommun::factory()->create();

    expect(KommunResource::canCreate())->toBeFalse()
        ->and(KommunResource::canEdit($kommun))->toBeFalse()
        ->and(KommunResource::canDelete($kommun))->toBeFalse();

    Livewire::test(ManageKommuner::class)
        ->assertActionDoesNotExist('create')
        ->assertTableActionDoesNotExist('edit')
        ->assertTableActionDoesNotExist('delete');
});

it('forbids users without an editorial role', function (User $user) {
    $this->actingAs($user)
        ->get(KommunResource::getUrl('index'))
        ->assertForbidden();
})->with([
    'no role' => fn () => User::factory()->create(),
    'verksamhetsägare' => fn () => User::factory()->verksamhetsagare()->create(),
]);
