<?php

namespace App\Observers;

use App\Enums\ContentStatus;
use App\Models\BusinessLocation;
use App\Models\Concerns\HasContentStatus;
use App\Models\OpeningHour;
use App\Models\OpeningHourException;
use App\Models\Slug;
use App\Support\ApiCache;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Tömmer API:ets svarscache när något som syns publikt ändras: raden är publicerad eller var
 * nyss publicerad (avpublicering). Rader utan egen status (slugs, bilder, öppettider) följer
 * sin ägare, och en lokalisering kräver dessutom ett publicerat företag. Ett utkast som
 * redigeras rör aldrig cachen.
 */
class ApiCacheObserver
{
    public function __construct(protected ApiCache $cache) {}

    public function saved(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    public function restored(Model $model): void
    {
        $this->flush($model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->flush($model);
    }

    protected function flush(Model $model): void
    {
        if ($this->touchesPublicContent($model)) {
            $this->cache->flushFor($model);
        }
    }

    protected function touchesPublicContent(Model $model): bool
    {
        return match (true) {
            $model instanceof Slug => $model->slugable !== null && $this->touchesPublicContent($model->slugable),
            $model instanceof Media => $model->model !== null && $this->touchesPublicContent($model->model),
            $model instanceof OpeningHour,
            $model instanceof OpeningHourException => $model->businessLocation !== null && $this->touchesPublicContent($model->businessLocation),
            $model instanceof BusinessLocation => $this->hasPublicStatus($model)
                && $model->business !== null && $this->touchesPublicContent($model->business),
            in_array(HasContentStatus::class, class_uses_recursive($model), true) => $this->hasPublicStatus($model),
            default => true,
        };
    }

    /** Publicerad nu, eller publicerad före den här sparningen (avpublicering). */
    protected function hasPublicStatus(Model $model): bool
    {
        return $model->isPublished() || $model->getOriginal('status') === ContentStatus::Published;
    }
}
