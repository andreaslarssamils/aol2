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
 * Kanonisk länk till innehåll som lyfts fram via promotions. Hemsajt = aktiv sajt vars
 * primärkommun är innehållets kommun, annars första aktiva sajt som visar kommunen.
 * Sökvägen per typ bor i config('api.frontend.paths'). Erbjudanden har ingen egen sida
 * och länkar till företagets primära lokalisering, om den finns i katalogen.
 */
class ContentUrl
{
    /**
     * @return array{type: string, slug: ?string, locale: string, kommun: ?string, site: ?string, path: ?string, url: ?string}|null
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
            'url' => $site === null || $path === null ? null : $this->baseUrl($site).$path,
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

    protected function baseUrl(Site $site): string
    {
        if ($site->domain !== null) {
            return 'https://'.$site->domain;
        }

        return rtrim((string) config('api.frontend.url'), '/').'/'.$site->slug;
    }
}
