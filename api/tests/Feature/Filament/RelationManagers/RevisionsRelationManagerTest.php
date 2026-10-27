<?php

use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Businesses\BusinessResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Pages\PageResource;
use App\Models\Article;
use App\Models\Revision;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists approved revisions with reviewer, time and comment, newest first', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $article = Article::factory()->create();
    $reviewer = User::factory()->redaktor()->create(['name' => 'Rita Redaktör']);
    $older = Revision::factory()->for($article, 'revisionable')->approved()->create([
        'reviewed_by' => $reviewer,
        'applied_at' => now()->subDay(),
        'review_comment' => 'Första versionen',
    ]);
    $newer = Revision::factory()->for($article, 'revisionable')->approved()->create(['applied_at' => now()]);
    $pending = Revision::factory()->for($article, 'revisionable')->create();
    $rejected = Revision::factory()->for($article, 'revisionable')->rejected()->create();
    $otherContent = Revision::factory()->approved()->create();

    Livewire::test(RevisionsRelationManager::class, ['ownerRecord' => $article, 'pageClass' => EditArticle::class])
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true)
        ->assertCanNotSeeTableRecords([$pending, $rejected, $otherContent])
        ->assertSee('Rita Redaktör')
        ->assertSee('Första versionen');
});

it('shows what a version contained compared with the current row', function () {
    $this->actingAs(User::factory()->admin()->create());
    $article = Article::factory()->create(['titel' => ['sv' => 'Nuvarande titel', 'en' => 'Current title']]);
    $revision = Revision::factory()->for($article, 'revisionable')->approved()->create([
        'payload' => ['titel' => ['sv' => 'Dåvarande titel', 'en' => 'Current title']],
    ]);

    Livewire::test(RevisionsRelationManager::class, ['ownerRecord' => $article, 'pageClass' => EditArticle::class])
        ->mountAction(TestAction::make('payload')->table($revision))
        ->assertMountedActionModalSee('Denna version')
        ->assertMountedActionModalSee('Nuvarande titel')
        ->assertMountedActionModalSee('Dåvarande titel');
});

it('is read-only: offers neither create nor edit or delete', function () {
    $this->actingAs(User::factory()->admin()->create());
    $article = Article::factory()->create();
    $revision = Revision::factory()->for($article, 'revisionable')->approved()->create();

    Livewire::test(RevisionsRelationManager::class, ['ownerRecord' => $article, 'pageClass' => EditArticle::class])
        ->assertActionDoesNotExist(TestAction::make('create')->table())
        ->assertActionDoesNotExist(TestAction::make('edit')->table($revision))
        ->assertActionDoesNotExist(TestAction::make('delete')->table($revision))
        ->assertActionExists(TestAction::make('payload')->table($revision));
});

it('is registered on article, event, page and business', function (string $resource) {
    expect($resource::getRelations())->toContain(RevisionsRelationManager::class);
})->with([
    'article' => ArticleResource::class,
    'event' => EventResource::class,
    'page' => PageResource::class,
    'business' => BusinessResource::class,
]);
