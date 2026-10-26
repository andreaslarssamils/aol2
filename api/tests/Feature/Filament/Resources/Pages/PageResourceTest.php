<?php

use App\Enums\ContentStatus;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Kommun;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists pages for the editorial team', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $pages = Page::factory()->count(2)->create();

    $this->get(PageResource::getUrl('index'))->assertOk();

    Livewire::test(ListPages::class)
        ->assertCanSeeTableRecords($pages);
});

it('creates a national page without a kommun', function () {
    $this->actingAs(User::factory()->redaktor()->create());

    Livewire::test(CreatePage::class)
        ->fillForm([
            'kommun_id' => null,
            'titel.sv' => 'Integritetspolicy',
            'titel.en' => 'Privacy policy',
            'slugs.sv' => 'integritetspolicy',
            'slugs.en' => '',
            'body.sv' => '<p>Vi värnar din integritet.</p>',
            'body.en' => '<p>We protect your privacy.</p>',
            'status' => ContentStatus::Draft->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::sole();

    expect($page->kommun_id)->toBeNull()
        ->and($page->titel['en'])->toBe('Privacy policy')
        ->and($page->slugFor('sv'))->toBe('integritetspolicy')
        ->and($page->slugFor('en'))->toBe('privacy-policy');
});

it('updates a page for a specific kommun', function () {
    $this->actingAs(User::factory()->admin()->create());
    $kommun = Kommun::factory()->create();
    $page = Page::factory()->create(['kommun_id' => null]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm([
            'kommun_id' => $kommun->id,
            'titel.sv' => 'Om Allt om Ludvika',
            'status' => ContentStatus::Published->value,
            'published_at' => '2026-01-01 08:00:00',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('pages', [
        'id' => $page->id,
        'kommun_id' => $kommun->id,
        'status' => ContentStatus::Published->value,
    ]);
    expect($page->refresh()->titel['sv'])->toBe('Om Allt om Ludvika');
});

it('forbids users without an editorial role', function (User $user) {
    $this->actingAs($user)
        ->get(PageResource::getUrl('index'))
        ->assertForbidden();
})->with([
    'no role' => fn () => User::factory()->create(),
    'verksamhetsägare' => fn () => User::factory()->verksamhetsagare()->create(),
]);
