<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\HasMedia;

/**
 * Bas för API:ets resurser. Översättningsbara json-fält blir en sträng i vald locale
 * (ResolveLocale) med fallback till standardspråket, och bilder hämtas ur collection "images".
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

    protected function images(HasMedia $model): AnonymousResourceCollection
    {
        return MediaResource::collection($model->getMedia(Business::MEDIA_IMAGES));
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
