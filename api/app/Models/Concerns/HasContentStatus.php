<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/** Modeller med status (ContentStatus) + published_at. */
trait HasContentStatus
{
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published;
    }
}
