<?php

use App\Enums\BusinessKind;
use App\Enums\BusinessUserRole;
use App\Enums\ContentStatus;
use App\Enums\ManagedBy;
use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\Businesses\Pages\CreateBusiness;
use App\Filament\Resources\Businesses\Pages\EditBusiness;
use App\Filament\Resources\Businesses\Pages\ListBusinesses;
use App\Filament\Resources\Businesses\RelationManagers\LocationsRelationManager;
use App\Filament\Resources\Businesses\RelationManagers\UsersRelationManager;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Kommun;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists businesses for the editorial team', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $businesses = Business::factory()->count(2)->create();

    $this->get(BusinessResource::getUrl('index'))->assertOk();

    Livewire::test(ListBusinesses::class)
        ->assertCanSeeTableRecords($businesses);
});

it('filters the list on the kommun of its locations', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $ludvika = Kommun::factory()->create();
    $inLudvika = BusinessLocation::factory()->create(['kommun_id' => $ludvika])->business;
    $elsewhere = BusinessLocation::factory()->create()->business;

    Livewire::test(ListBusinesses::class)
        ->filterTable('kommun', $ludvika->id)
        ->assertCanSeeTableRecords([$inLudvika])
        ->assertCanNotSeeTableRecords([$elsewhere]);
});

it('creates a published association with categories and a translated description', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $category = Category::factory()->create();

    Livewire::test(CreateBusiness::class)
        ->fillForm([
            'namn' => 'Ludvika IF',
            'kind' => BusinessKind::Association->value,
            'managed_by' => ManagedBy::Editorial->value,
            'epost' => 'kansli@ludvikaif.se',
            'webbplats' => 'https://ludvikaif.se',
            'categories' => [$category->id],
            'beskrivning.sv' => '<p>Fotboll sedan 1909.</p>',
            'beskrivning.en' => '<p>Football since 1909.</p>',
            'status' => ContentStatus::Published->value,
            'published_at' => '2026-10-26 12:00:00',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $business = Business::sole();

    expect($business->kind)->toBe(BusinessKind::Association)
        ->and($business->status)->toBe(ContentStatus::Published)
        ->and($business->beskrivning)->toEqual(['sv' => '<p>Fotboll sedan 1909.</p>', 'en' => '<p>Football since 1909.</p>'])
        ->and($business->categories->pluck('id')->all())->toBe([$category->id]);
});

it('updates a business', function () {
    $this->actingAs(User::factory()->admin()->create());
    $business = Business::factory()->create();

    Livewire::test(EditBusiness::class, ['record' => $business->getRouteKey()])
        ->fillForm([
            'namn' => 'Nytt namn AB',
            'managed_by' => ManagedBy::Self->value,
            'auto_approve' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('businesses', [
        'id' => $business->id,
        'namn' => 'Nytt namn AB',
        'managed_by' => ManagedBy::Self->value,
        'auto_approve' => true,
    ]);
});

it('creates a location with opening hours, an exception and a slug through the relation manager', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $business = Business::factory()->create(['namn' => 'Café Kaka']);
    $kommun = Kommun::factory()->create();

    Livewire::test(LocationsRelationManager::class, [
        'ownerRecord' => $business,
        'pageClass' => EditBusiness::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'kommun_id' => $kommun->id,
            'namn' => null,
            'adress' => 'Storgatan 1',
            'postnummer' => '771 30',
            'ort' => 'Ludvika',
            'is_primary' => true,
            'slugs' => ['sv' => '', 'en' => 'cafe-kaka-ludvika'],
            'openingHours' => [
                ['weekday' => 0, 'opens' => '11:00', 'closes' => '14:00', 'closed' => false],
                ['weekday' => 0, 'opens' => '17:00', 'closes' => '21:00', 'closed' => false],
                ['weekday' => 6, 'opens' => null, 'closes' => null, 'closed' => true],
            ],
            'openingHourExceptions' => [
                ['date' => '2026-12-24', 'opens' => null, 'closes' => null, 'closed' => true, 'note' => 'Julafton'],
            ],
        ])
        ->assertHasNoActionErrors();

    $location = BusinessLocation::sole();

    expect($location->business->is($business))->toBeTrue()
        ->and($location->kommun_id)->toBe($kommun->id)
        ->and($location->display_name)->toBe('Café Kaka')
        ->and($location->slugFor('sv'))->toBe('cafe-kaka')
        ->and($location->slugFor('en'))->toBe('cafe-kaka-ludvika')
        ->and($location->openingHours->pluck('opens')->all())->toBe(['11:00:00', '17:00:00', null])
        ->and($location->openingHourExceptions)->toHaveCount(1)
        ->and($location->openingHourExceptions->first()->note)->toBe('Julafton');
});

it('edits a location and its opening hours through the relation manager', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $location = BusinessLocation::factory()->create();
    $location->openingHours()->create(['weekday' => 2, 'opens' => '09:00', 'closes' => '17:00']);

    Livewire::test(LocationsRelationManager::class, [
        'ownerRecord' => $location->business,
        'pageClass' => EditBusiness::class,
    ])
        ->callAction(TestAction::make('edit')->table($location), [
            'namn' => 'Acme Centrum',
            'openingHours' => [
                ['weekday' => 2, 'opens' => '10:00', 'closes' => '18:00', 'closed' => false],
            ],
        ])
        ->assertHasNoActionErrors();

    $location->refresh();

    expect($location->namn)->toBe('Acme Centrum')
        ->and($location->openingHours)->toHaveCount(1)
        ->and($location->openingHours->first()->opens)->toBe('10:00:00');
});

it('attaches a user with a role through the relation manager', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $business = Business::factory()->create();
    $owner = User::factory()->verksamhetsagare()->create();

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $business,
        'pageClass' => EditBusiness::class,
    ])
        ->callAction(TestAction::make('attach')->table(), [
            'recordId' => $owner->id,
            'role' => BusinessUserRole::Manager->value,
        ])
        ->assertHasNoActionErrors()
        ->assertCanSeeTableRecords([$owner]);

    $this->assertDatabaseHas('business_user', [
        'business_id' => $business->id,
        'user_id' => $owner->id,
        'role' => BusinessUserRole::Manager->value,
    ]);
    expect($business->users()->first()->pivot->created_at)->not->toBeNull();
});

it('forbids users without an editorial role', function (User $user) {
    $this->actingAs($user)
        ->get(BusinessResource::getUrl('index'))
        ->assertForbidden();
})->with([
    'no role' => fn () => User::factory()->create(),
    'verksamhetsägare' => fn () => User::factory()->verksamhetsagare()->create(),
]);
