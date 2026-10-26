<?php

use App\Enums\CategoryType;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists categories for the editorial team', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $categories = Category::factory()->count(2)->create();

    $this->get(CategoryResource::getUrl('index'))->assertOk();

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords($categories);
});

it('creates a translated event category under a parent', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $parent = Category::factory()->event()->create();

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'type' => CategoryType::Event->value,
            'parent_id' => $parent->id,
            'slug' => 'livemusik',
            'sort_order' => 3,
            'namn.sv' => 'Livemusik',
            'namn.en' => 'Live music',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'slug' => 'livemusik',
        'type' => CategoryType::Event->value,
        'parent_id' => $parent->id,
        'sort_order' => 3,
    ]);
    expect(Category::where('slug', 'livemusik')->sole()->namn)->toEqual(['sv' => 'Livemusik', 'en' => 'Live music']);
});

it('rejects a slug that is already used by a category of the same type', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    Category::factory()->create(['slug' => 'butik', 'type' => CategoryType::Business]);

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'type' => CategoryType::Business->value,
            'slug' => 'butik',
            'namn.sv' => 'Butik',
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'unique']);

    expect(Category::where('slug', 'butik')->count())->toBe(1);
});

it('updates a category name and sort order', function () {
    $this->actingAs(User::factory()->admin()->create());
    $category = Category::factory()->create(['sort_order' => 0]);

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm([
            'namn.sv' => 'Restaurang & café',
            'namn.en' => 'Restaurants & cafés',
            'sort_order' => 7,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $category->refresh();

    expect($category->namn)->toEqual(['sv' => 'Restaurang & café', 'en' => 'Restaurants & cafés'])
        ->and($category->sort_order)->toBe(7);
});

it('forbids users without an editorial role', function (User $user) {
    $this->actingAs($user)
        ->get(CategoryResource::getUrl('index'))
        ->assertForbidden();
})->with([
    'no role' => fn () => User::factory()->create(),
    'verksamhetsägare' => fn () => User::factory()->verksamhetsagare()->create(),
]);
