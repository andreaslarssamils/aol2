<?php

use App\Jobs\PruneOrphanMedia;
use App\Models\Business;
use App\Models\Revision;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

it('removes pending media older than 30 days without a pending revision, and nothing else', function () {
    Storage::fake('public');
    $upload = fn (HasMedia $model, string $collection, int $daysAgo): Media => $this->travelTo(now()->subDays($daysAgo), fn (): Media => $model
        ->addMedia(UploadedFile::fake()->create('bild.jpg', 10, 'image/jpeg'))
        ->toMediaCollection($collection));

    $stale = Business::factory()->create();
    $staleMedia = $upload($stale, Business::MEDIA_PENDING, 31);

    $staleWithRejected = Business::factory()->create();
    $staleWithRejectedMedia = $upload($staleWithRejected, Business::MEDIA_PENDING, 31);
    Revision::factory()->for($staleWithRejected, 'revisionable')->rejected()->create();

    $staleButPending = Business::factory()->create();
    $staleButPendingMedia = $upload($staleButPending, Business::MEDIA_PENDING, 31);
    Revision::factory()->for($staleButPending, 'revisionable')->create();

    $fresh = Business::factory()->create();
    $freshMedia = $upload($fresh, Business::MEDIA_PENDING, 29);

    $published = Business::factory()->create();
    $publishedMedia = $upload($published, Business::MEDIA_IMAGES, 60);

    (new PruneOrphanMedia)->handle();

    expect(Media::query()->pluck('id')->all())->toEqualCanonicalizing([
        $staleButPendingMedia->id,
        $freshMedia->id,
        $publishedMedia->id,
    ]);
    Storage::disk('public')->assertMissing($staleMedia->getPathRelativeToRoot());
    Storage::disk('public')->assertMissing($staleWithRejectedMedia->getPathRelativeToRoot());
    Storage::disk('public')->assertExists($freshMedia->getPathRelativeToRoot());
});

it('is scheduled daily', function () {
    $scheduled = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => $event->description === PruneOrphanMedia::class);

    expect($scheduled)->not->toBeNull()
        ->and($scheduled->expression)->toBe('0 0 * * *');
});
