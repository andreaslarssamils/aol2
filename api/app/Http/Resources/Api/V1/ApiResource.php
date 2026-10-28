<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Business;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\HasMedia;

/**
 * Bas för API:ets resurser. Översättningsbara json-fält blir en sträng i vald locale
 * (ResolveLocale) med fallback till standardspråket, tidpunkter blir ISO 8601 med offset i
 * visningstidszonen, bilder hämtas ur collection "images" och detaljsvar ger slugs per språk.
 */
abstract class ApiResource extends JsonResource
{
    protected function locale(Request $request): string
    {
        return $request->attributes->get('locale') ?? static::defaultLocale();
    }

    /**
     * @param  array<string, ?string>|null  $value
     */
    protected function translate(?array $value, Request $request): ?string
    {
        $text = $value[$this->locale($request)] ?? null;

        return filled($text) ? $text : ($value[static::defaultLocale()] ?? null);
    }

    /** Kanonisk slug per språk för detaljsvar – ett objekt även när det är tomt. */
    protected function slugs(): object
    {
        return (object) $this->resource->canonicalSlugs();
    }

    protected function images(HasMedia $model): AnonymousResourceCollection
    {
        return MediaResource::collection($model->getMedia(Business::MEDIA_IMAGES));
    }

    /** Tidpunkt som ISO 8601 med offset i visningstidszonen, t.ex. 2026-11-03T19:00:00+01:00. Lagringen är UTC. */
    protected function dateTime(?CarbonInterface $value): ?string
    {
        return $value?->copy()->setTimezone(config('app.display_timezone'))->toIso8601String();
    }

    protected function coordinate(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    protected static function defaultLocale(): string
    {
        return array_key_first(config('app.locales'));
    }
}
