<?php

namespace App\Models\Concerns;

use App\Enums\RevisionStatus;
use App\Models\Revision;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Innehåll som går genom godkännandeflödet. Ändringar sparas som
 * revisioner och appliceras på modellen först när de godkänns.
 */
trait HasRevisions
{
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable');
    }

    public function latestPendingRevision(): MorphOne
    {
        return $this->morphOne(Revision::class, 'revisionable')
            ->where('status', RevisionStatus::Pending)
            ->latestOfMany();
    }
}
