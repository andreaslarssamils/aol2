<?php

namespace App\Actions\Revisions;

use App\Enums\RevisionStatus;
use App\Models\Business;
use App\Models\Revision;
use App\Models\User;
use InvalidArgumentException;
use LogicException;
use Spatie\MediaLibrary\HasMedia;

/**
 * Avslår en väntande revision med kommentar. Rör aldrig raden i innehållstabellen, men
 * raderar innehållets väntande media i "pending" – bilderna hörde till den avslagna ändringen.
 */
class RejectRevision
{
    public function handle(Revision $revision, User $reviewer, string $comment): Revision
    {
        if (blank($comment)) {
            throw new InvalidArgumentException('Ett avslag kräver en kommentar till den som skickade in ändringen.');
        }

        if ($revision->status !== RevisionStatus::Pending) {
            throw new LogicException("Revision {$revision->id} är redan {$revision->status->getLabel()}.");
        }

        $revision->update([
            'status' => RevisionStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_comment' => $comment,
        ]);

        $model = $revision->revisionable;

        if ($model instanceof HasMedia) {
            $model->clearMediaCollection(Business::MEDIA_PENDING);
        }

        return $revision;
    }
}
