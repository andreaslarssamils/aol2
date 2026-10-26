<?php

use App\Enums\SiteStatus;
use App\Filament\Resources\Sites\Pages\CreateSite;
use App\Filament\Resources\Sites\Pages\EditSite;
use App\Filament\Resources\Sites\Pages\ListSites;
use App\Filament\Resources\Sites\RelationManagers\KommunerRelationManager;
use App\Filament\Resources\Sites\SiteResource;
use App\Models\Kommun;
use App\Models\Site;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists sites for the editorial team', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $sites = Site::factory()->count(2)->create();

    $this->get(SiteResource::getUrl('index'))->assertOk();

    Livewire::test(ListSites::class)
        ->assertCanSeeTableRecords($sites);
});

it('filters the list on kommun', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $ludvika = Kommun::factory()->create();
    $site = Site::factory()->create(['primary_kommun_id' => $ludvika]);
    $other = Site::factory()->create();

    Livewire::test(ListSites::class)
        ->filterTable('kommuner', $ludvika->id)
        ->assertCanSeeTableRecords([$site])
        ->assertCanNotSeeTableRecords([$other]);
});

it('creates a site and shows its primary kommun automatically', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $ludvika = Kommun::factory()->create();

    Livewire::test(CreateSite::class)
        ->fillForm([
            'namn' => 'Allt om Ludvika',
            'slug' => 'ludvika',
            'domain' => 'alltomludvika.se',
            'primary_kommun_id' => $ludvika->id,
            'status' => SiteStatus::Active->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $site = Site::sole();

    expect($site->status)->toBe(SiteStatus::Active)
        ->and($site->primaryKommun->is($ludvika))->toBeTrue()
        ->and($site->kommunIds())->toBe([$ludvika->id]);
});

it('updates a site', function () {
    $this->actingAs(User::factory()->admin()->create());
    $site = Site::factory()->create();

    Livewire::test(EditSite::class, ['record' => $site->getRouteKey()])
        ->fillForm([
            'namn' => 'Allt om Västerbergslagen',
            'slug' => 'vasterbergslagen',
            'status' => SiteStatus::Hidden->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('sites', [
        'id' => $site->id,
        'namn' => 'Allt om Västerbergslagen',
        'slug' => 'vasterbergslagen',
        'status' => SiteStatus::Hidden->value,
    ]);
});

it('attaches another kommun with a sort order through the relation manager', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $site = Site::factory()->create();
    $smedjebacken = Kommun::factory()->create();

    Livewire::test(KommunerRelationManager::class, [
        'ownerRecord' => $site,
        'pageClass' => EditSite::class,
    ])
        ->assertCanSeeTableRecords([$site->primaryKommun])
        ->callAction(TestAction::make('attach')->table(), [
            'recordId' => $smedjebacken->id,
            'sort_order' => 1,
        ])
        ->assertHasNoActionErrors()
        ->assertCanSeeTableRecords([$site->primaryKommun, $smedjebacken]);

    $this->assertDatabaseHas('site_kommun', [
        'site_id' => $site->id,
        'kommun_id' => $smedjebacken->id,
        'sort_order' => 1,
    ]);
});

it('forbids users without an editorial role', function (User $user) {
    $this->actingAs($user)
        ->get(SiteResource::getUrl('index'))
        ->assertForbidden();
})->with([
    'no role' => fn () => User::factory()->create(),
    'verksamhetsägare' => fn () => User::factory()->verksamhetsagare()->create(),
]);
