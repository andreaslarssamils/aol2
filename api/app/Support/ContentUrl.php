<?php

namespace App\Support;

use App\Models\BusinessLocation;
use App\Models\Concerns\HasSlugs;
use App\Models\Kommun;
use App\Models\Offer;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Sajtrelativ länk till innehåll som lyfts fram via promotions: sökvägssegmentet per typ
 * (config('api.frontend.paths')) plus kanonisk slug för valt språk – utan origin och utan
 * språkprefix, frontend lägger på båda. Hemsajt = aktiv sajt vars primärkommun är innehållets
 * kommun, annars första aktiva sajt som visar kommunen. Erbjudanden har ingen egen sida och
 * länkar till företagets primära lokalisering, om den finns i katalogen.
 */
class ContentUrl
{
    /**
     * @return array{type: string, slug: ?string, locale: string, kommun: ?string, site: ?string, path: ?string}|null
     */
    public function for(?Model $content, string $locale): ?array
    {
        $target = $content instanceof Offer ? $content->business?->primaryLocation : $content;

        if ($target === null || ! in_array(HasSlugs::class, class_uses_recursive($target), true)) {
            return null;
        }

        if ($target instanceof BusinessLocation && ! $target->isInCatalog()) {
            return null;
        }

        $type = $target->getMorphClass();
        $slug = $target->localizedSlug($locale);
        $kommun = $target->kommun;
        $site = $kommun === null ? null : $this->homeSite($kommun);
        $segment = trim((string) config("api.frontend.paths.{$type}", $type), '/');
        $path = $slug === null ? null : '/'.ltrim("{$segment}/{$slug}", '/');

        return [
            'type' => $type,
            'slug' => $slug,
            'locale' => $locale,
            'kommun' => $kommun?->slug,
            'site' => $site?->slug,
            'path' => $path,
        ];
    }

    protected function homeSite(Kommun $kommun): ?Site
    {
        return Site::query()
            ->active()
            ->whereHas('kommuner', fn (Builder $kommuner) => $kommuner->whereKey($kommun->id))
            ->orderByRaw('(primary_kommun_id = ?) DESC', [$kommun->id])
            ->orderBy('id')
            ->first();
    }
}
