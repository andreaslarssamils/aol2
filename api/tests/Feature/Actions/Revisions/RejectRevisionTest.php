<?php

use App\Actions\Revisions\RejectRevision;
use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Models\Business;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('marks the revision rejected with reviewer and comment, deletes its pending media and leaves the row untouched', function () {
    Storage::fake('public');
    $this->freezeSecond();
    $business = Business::factory()->create(['namn' => 'Gamla namnet']);
    $pendingMedia = $business->addMedia(UploadedFile::fake()->create('vantande.jpg', 10, 'image/jpeg'))->toMediaCollection(Business::MEDIA_PENDING);
    $business->addMedia(UploadedFile::fake()->create('publicerad.jpg', 10, 'image/jpeg'))->toMediaCollection(Business::MEDIA_IMAGES);
    $revision = Revision::factory()->for($business, 'revisionable')->create(['payload' => ['namn' => 'Nya namnet']]);
    $reviewer = User::factory()->redaktor()->create();

    app(RejectRevision::class)->handle($revision, $reviewer, 'Namnet stämmer inte med bolagsregistret.');

    $revision->refresh();

    expect($revision->status)->toBe(RevisionStatus::Rejected)
        ->and($revision->reviewed_by)->toBe($reviewer->id)
        ->and($revision->reviewed_at)->toEqual(now())
        ->and($revision->review_comment)->toBe('Namnet stämmer inte med bolagsregistret.')
        ->and($revision->applied_at)->toBeNull();

    $business->refresh();

    expect($business->namn)->toBe('Gamla namnet')
        ->and($business->status)->toBe(ContentStatus::Draft)
        ->and($business->published_at)->toBeNull()
        ->and($business->getMedia(Business::MEDIA_PENDING))->toHaveCount(0)
        ->and($business->getMedia(Business::MEDIA_IMAGES)->pluck('file_name')->all())->toBe(['publicerad.jpg']);

    $this->assertModelMissing($pendingMedia);
    Storage::disk('public')->assertMissing($pendingMedia->getPathRelativeToRoot());
});

it('requires a comment', function () {
    $revision = Revision::factory()->create();

    expect(fn () => app(RejectRevision::class)->handle($revision, User::factory()->redaktor()->create(), '   '))
        ->toThrow(InvalidArgumentException::class);

    expect($revision->fresh()->status)->toBe(RevisionStatus::Pending);
});

it('refuses to reject a revision that is no longer pending', function (Revision $revision) {
    $status = $revision->status;

    expect(fn () => app(RejectRevision::class)->handle($revision, User::factory()->redaktor()->create(), 'Nej'))
        ->toThrow(LogicException::class);

    expect($revision->fresh()->status)->toBe($status);
})->with([
    'approved' => fn () => Revision::factory()->approved()->create(),
    'already rejected' => fn () => Revision::factory()->rejected()->create(),
]);
