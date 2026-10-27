<?php

use App\Actions\Revisions\ApproveRevision;
use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\Business;
use App\Models\Category;
use App\Models\Event;
use App\Models\Page;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('writes the payload to the row, publishes it, moves pending media to images and stamps the revision', function () {
    Storage::fake('public');
    $this->freezeSecond();
    $article = Article::factory()->create(['titel' => ['sv' => 'Gammal titel', 'en' => 'Old title'], 'featured' => false]);
    $article->addMedia(UploadedFile::fake()->create('vantande.jpg', 10, 'image/jpeg'))->toMediaCollection(Business::MEDIA_PENDING);
    $article->addMedia(UploadedFile::fake()->create('publicerad.jpg', 10, 'image/jpeg'))->toMediaCollection(Business::MEDIA_IMAGES);
    $revision = Revision::factory()->for($article, 'revisionable')->create(['payload' => [
        'titel' => ['sv' => 'Ny titel', 'en' => 'New title'],
        'featured' => true,
        'slugs' => ['sv' => 'ny-titel'],
    ]]);
    $reviewer = User::factory()->redaktor()->create();

    app(ApproveRevision::class)->handle($revision, $reviewer, 'Ser bra ut');

    $article->refresh();

    expect($article->titel)->toEqual(['sv' => 'Ny titel', 'en' => 'New title'])
        ->and($article->featured)->toBeTrue()
        ->and($article->status)->toBe(ContentStatus::Published)
        ->and($article->published_at)->toEqual(now())
        ->and($article->slugFor('sv'))->toBe('ny-titel')
        ->and($article->getMedia(Business::MEDIA_IMAGES)->pluck('file_name')->all())->toEqualCanonicalizing(['vantande.jpg', 'publicerad.jpg'])
        ->and($article->getMedia(Business::MEDIA_PENDING))->toHaveCount(0);

    // Gamla sluggen blir en omdirigering i stället för att raderas.
    $this->assertDatabaseHas('slugs', ['slugable_id' => $article->id, 'locale' => 'sv', 'slug' => 'gammal-titel', 'is_canonical' => false]);

    $revision->refresh();

    expect($revision->status)->toBe(RevisionStatus::Approved)
        ->and($revision->reviewed_by)->toBe($reviewer->id)
        ->and($revision->reviewed_at)->toEqual(now())
        ->and($revision->applied_at)->toEqual(now())
        ->and($revision->review_comment)->toBe('Ser bra ut');
});

it('syncs categories from the payload', function () {
    $event = Event::factory()->create();
    $old = Category::factory()->event()->create();
    $new = Category::factory()->event()->create();
    $event->categories()->attach($old);
    $revision = Revision::factory()->for($event, 'revisionable')->create(['payload' => ['categories' => [$new->id]]]);

    app(ApproveRevision::class)->handle($revision, User::factory()->redaktor()->create());

    expect($event->fresh()->categories->pluck('id')->all())->toBe([$new->id]);
});

it('keeps the original published_at of already published content', function () {
    $page = Page::factory()->published()->create(['published_at' => '2026-01-01 08:00:00']);
    $revision = Revision::factory()->for($page, 'revisionable')->create(['payload' => ['titel' => ['sv' => 'Om oss', 'en' => 'About us']]]);

    app(ApproveRevision::class)->handle($revision, User::factory()->redaktor()->create());

    $page->refresh();

    expect($page->titel['sv'])->toBe('Om oss')
        ->and($page->published_at->toDateTimeString())->toBe('2026-01-01 08:00:00');
});

it('refuses to approve a revision that is no longer pending', function (Revision $revision) {
    $status = $revision->status;

    expect(fn () => app(ApproveRevision::class)->handle($revision, User::factory()->redaktor()->create()))
        ->toThrow(LogicException::class);

    expect($revision->fresh()->status)->toBe($status);
    $this->assertDatabaseMissing('businesses', ['namn' => $revision->payload['namn']]);
})->with([
    'rejected' => fn () => Revision::factory()->rejected()->create(),
    'already approved' => fn () => Revision::factory()->approved()->create(),
]);

it('refuses to approve a revision whose content has been deleted', function () {
    $article = Article::factory()->create();
    $revision = Revision::factory()->for($article, 'revisionable')->create();
    $article->delete();

    expect(fn () => app(ApproveRevision::class)->handle($revision, User::factory()->redaktor()->create()))
        ->toThrow(LogicException::class);

    expect($revision->fresh()->status)->toBe(RevisionStatus::Pending);
});
