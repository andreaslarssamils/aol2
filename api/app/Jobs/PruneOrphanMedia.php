<?php

namespace App\Jobs;

use App\Enums\RevisionStatus;
use App\Models\Business;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Städar ägaruppladdningar som blivit liggande: media i "pending" äldre än 30 dagar utan väntande
 * revision. Avslagna revisioners media raderas direkt av RejectRevision. Schemalagt dagligen i
 * routes/console.php – utan det växer lagringen tyst.
 */
class PruneOrphanMedia implements ShouldQueue
{
    use Queueable;

    public const int MAX_AGE_DAYS = 30;

    public function handle(): void
    {
        Media::query()
            ->where('collection_name', Business::MEDIA_PENDING)
            ->where('media.created_at', '<', now()->subDays(self::MAX_AGE_DAYS))
            ->whereNotExists(fn (QueryBuilder $revisions) => $revisions
                ->select(DB::raw('1'))
                ->from('revisions')
                ->whereColumn('revisions.revisionable_type', 'media.model_type')
                ->whereColumn('revisions.revisionable_id', 'media.model_id')
                ->where('revisions.status', RevisionStatus::Pending->value))
            ->lazyById()
            ->each(fn (Media $media) => $media->delete());
    }
}
