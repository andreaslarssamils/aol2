<?php

use App\Enums\ContentStatus;
use App\Filament\Forms\Components\RichText;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Models\Article;
use App\Models\Kommun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists articles newest first for the editorial team', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $older = Article::factory()->create(['created_at' => now()->subDay()]);
    $newer = Article::factory()->create(['created_at' => now()]);

    $this->get(ArticleResource::getUrl('index'))->assertOk();

    Livewire::test(ListArticles::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

it('filters the list on kommun and status', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $ludvika = Kommun::factory()->create();
    $inLudvika = Article::factory()->published()->create(['kommun_id' => $ludvika]);
    $draftInLudvika = Article::factory()->create(['kommun_id' => $ludvika]);
    $elsewhere = Article::factory()->published()->create();

    Livewire::test(ListArticles::class)
        ->filterTable('kommun_id', $ludvika->id)
        ->assertCanSeeTableRecords([$inLudvika, $draftInLudvika])
        ->assertCanNotSeeTableRecords([$elsewhere])
        ->filterTable('status', ContentStatus::Published->value)
        ->assertCanSeeTableRecords([$inLudvika])
        ->assertCanNotSeeTableRecords([$draftInLudvika, $elsewhere]);
});

it('creates a published article with translations and one canonical slug per locale', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $kommun = Kommun::factory()->create();

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'kommun_id' => $kommun->id,
            'author_name' => 'Anna Andersson',
            'titel.sv' => 'Midsommar i Ludvika',
            'titel.en' => 'Midsummer in Ludvika',
            'slugs.sv' => '',
            'slugs.en' => 'Midsummer 2026',
            'ingress.sv' => 'Så firas midsommar.',
            'body.sv' => '<p>Brödtext</p>',
            'status' => ContentStatus::Published->value,
            'published_at' => '2026-06-19 10:00:00',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $article = Article::sole();

    expect($article->titel)->toEqual(['sv' => 'Midsommar i Ludvika', 'en' => 'Midsummer in Ludvika'])
        ->and($article->status)->toBe(ContentStatus::Published)
        ->and($article->kommun_id)->toBe($kommun->id)
        ->and($article->author_name)->toBe('Anna Andersson');

    // Tom slug genereras från titeln; angiven slug normaliseras. Ingen slug skrivs direkt till modellen.
    expect($article->slugFor('sv'))->toBe('midsommar-i-ludvika')
        ->and($article->slugFor('en'))->toBe('midsummer-2026')
        ->and($article->slugs()->count())->toBe(2);
});

it('requires a Swedish title and kommun', function () {
    $this->actingAs(User::factory()->redaktor()->create());

    Livewire::test(CreateArticle::class)
        ->fillForm(['titel.sv' => null, 'kommun_id' => null])
        ->call('create')
        ->assertHasFormErrors(['titel.sv' => 'required', 'kommun_id' => 'required']);

    expect(Article::count())->toBe(0);
});

it('updates an article and keeps the old slug as a redirect', function () {
    $this->actingAs(User::factory()->admin()->create());
    $article = Article::factory()->create(['titel' => ['sv' => 'Gammal titel', 'en' => 'Old title']]);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertSchemaStateSet(['titel.sv' => 'Gammal titel', 'slugs.sv' => 'gammal-titel'])
        ->fillForm([
            'titel.sv' => 'Ny titel',
            'slugs.sv' => 'ny-titel',
            'status' => ContentStatus::Archived->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $article->refresh();

    expect($article->titel['sv'])->toBe('Ny titel')
        ->and($article->status)->toBe(ContentStatus::Archived)
        ->and($article->slugFor('sv'))->toBe('ny-titel');

    $this->assertDatabaseHas('slugs', [
        'slugable_id' => $article->id,
        'locale' => 'sv',
        'slug' => 'gammal-titel',
        'is_canonical' => false,
    ]);
});

it('forbids users without an editorial role', function (User $user) {
    $this->actingAs($user)
        ->get(ArticleResource::getUrl('index'))
        ->assertForbidden();
})->with([
    'no role' => fn () => User::factory()->create(),
    'verksamhetsägare' => fn () => User::factory()->verksamhetsagare()->create(),
]);

it('redirects guests to the login page', function () {
    $this->get(ArticleResource::getUrl('index'))
        ->assertRedirect();
});

it('keeps inline images from the public disk in the body', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $image = Storage::disk('public')->url('inline/bild.jpg');

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'kommun_id' => Kommun::factory()->create()->id,
            'titel.sv' => 'Bildartikel',
            'body.sv' => "<p>Hej</p><img src=\"{$image}\" alt=\"Bild\"><img src=\"https://evil.test/x.png\">",
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $body = Article::sole()->body['sv'];

    expect($body)->toContain("<img src=\"{$image}\"")
        ->and($body)->not->toContain('evil.test');
});

it('uploads inline images to the public disk under inline/', function () {
    $editor = RichText::make('body.sv');

    expect($editor->getFileAttachmentsDiskName())->toBe('public')
        ->and($editor->getFileAttachmentsDirectory())->toBe('inline');
});
