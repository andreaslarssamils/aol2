<?php

namespace App\Actions\Revisions;

use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Models\Business;
use App\Models\Concerns\HasContentStatus;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\MediaLibrary\HasMedia;

/**
 * Godkänner en väntande revision. I en transaktion: payload skrivs till raden, status sätts
 * till published (published_at stämplas om det saknas), väntande media flyttas från "pending"
 * till "images" och revisionen stämplas reviewed_by/reviewed_at/applied_at.
 * Godkända revisioner raderas aldrig – de är versionshistoriken.
 */
class ApproveRevision
{
    /** @param  ?User  $reviewer  null = godkänd automatiskt (auto_approve) */
    public function handle(Revision $revision, ?User $reviewer, ?string $comment = null): Revision
    {
        return DB::transaction(function () use ($revision, $reviewer, $comment): Revision {
            // Radlås så att två granskare inte kan godkänna samma revision samtidigt.
            Revision::query()->whereKey($revision->getKey())->lockForUpdate()->firstOrFail();
            $revision->refresh();

            if ($revision->status !== RevisionStatus::Pending) {
                throw new LogicException("Revision {$revision->id} är redan {$revision->status->getLabel()}.");
            }

            $model = $revision->revisionable
                ?? throw new LogicException("Innehållet som revision {$revision->id} gäller finns inte längre.");

            $this->apply($model, $revision->payload);
            $this->publishPendingMedia($model);

            $revision->update([
                'status' => RevisionStatus::Approved,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
                'review_comment' => $comment,
                'applied_at' => now(),
            ]);

            return $revision;
        });
    }

    /**
     * Skriver payload till raden. fill() släpper bara igenom fillable-fält; slugs går via
     * setSlug() (gamla sluggen blir 301) och kategorier synkas som relation.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function apply(Model $model, array $payload): void
    {
        $model->fill(Arr::except($payload, ['slugs', 'categories']));

        if (in_array(HasContentStatus::class, class_uses_recursive($model), true)) {
            $model->status = ContentStatus::Published;

            // Erbjudanden har status men inget publiceringsdatum.
            if ($model->hasCast('published_at') && $model->published_at === null) {
                $model->published_at = now();
            }
        }

        $model->save();

        foreach ($payload['slugs'] ?? [] as $locale => $slug) {
            $model->setSlug($locale, $slug);
        }

        if (array_key_exists('categories', $payload)) {
            $model->categories()->sync($payload['categories'] ?? []);
        }
    }

    /** Ägaruppladdningar väntar i "pending" tills ändringen är godkänd – annars smiter bilder förbi granskningen. */
    protected function publishPendingMedia(Model $model): void
    {
        if (! $model instanceof HasMedia) {
            return;
        }

        $model->media()
            ->where('collection_name', Business::MEDIA_PENDING)
            ->update(['collection_name' => Business::MEDIA_IMAGES]);
    }
}
