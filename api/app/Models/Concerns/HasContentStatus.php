<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/** Modeller med status (ContentStatus) + published_at. */
trait HasContentStatus
{
    /** Kolumnen kvalificeras så att scopet fungerar även i joins där flera tabeller har status. */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where($query->qualifyColumn('status'), ContentStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published;
    }
}
