<?php

namespace App\Models\Concerns;

use App\Enums\RevisionStatus;
use App\Models\Business;
use App\Models\Kommun;
use App\Models\Revision;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Innehåll som går genom godkännandeflödet. Ändringar sparas som revisioner
 * (App\Actions\Revisions) och appliceras på modellen först när de godkänns.
 */
trait HasRevisions
{
    /** Form request vars regler en revisions payload valideras mot vid inskick. */
    abstract public static function revisionRequest(): FormRequest;

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

    /** Verksamheten vars auto_approve avgör om revisioner godkänns direkt. null = går alltid via kön. */
    public function revisionBusiness(): ?Business
    {
        return null;
    }

    /** Rubrik i granskningskön och versionshistoriken. */
    public function revisionTitle(): string
    {
        $title = $this->getAttribute('namn')
            ?? data_get($this->getAttribute('titel'), array_key_first(config('app.locales')));

        return filled($title) ? $title : class_basename($this).' #'.$this->getKey();
    }

    /** Kommunen som visas i granskningskön. */
    public function revisionKommun(): ?Kommun
    {
        return $this->kommun;
    }

    /** Innehåll i en viss kommun – kommunfiltret i granskningskön. */
    #[Scope]
    protected function inKommun(Builder $query, Kommun|int $kommun): void
    {
        $query->where('kommun_id', $kommun instanceof Kommun ? $kommun->id : $kommun);
    }
}
