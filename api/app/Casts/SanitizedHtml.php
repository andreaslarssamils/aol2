<?php

namespace App\Casts;

use App\Support\HtmlSanitizer;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Model;

/**
 * Json-kolumn med rich text – {"sv": "<p>…</p>", "en": …} eller lunchmenyns {"mon": ["…"]} –
 * som saneras med editorial-profilen varje gång den sätts, oavsett om skrivningen kommer från
 * Filament, en godkänd revision, en seeder eller tinker. Ägarnas snävare profil appliceras
 * tidigare, i SubmitRevision. Läses som vanlig array, precis som casten 'array'.
 *
 * @implements CastsAttributes<mixed, mixed>
 */
class SanitizedHtml implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value === null ? null : Json::decode($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = app(HtmlSanitizer::class)->sanitizeValue($value);
        $stored = $attributes[$key] ?? null;

        // Oförändrat innehåll behåller databasens json-sträng, annars blir raden dirty i onödan.
        if (is_string($stored) && Json::decode($stored) === $sanitized) {
            return $stored;
        }

        return Json::encode($sanitized);
    }

    /**
     * Kolumnerna på modellen som har den här casten – de SubmitRevision sanerar i payloaden.
     *
     * @return list<string>
     */
    public static function columns(Model $model): array
    {
        return array_keys(array_filter(
            $model->getCasts(),
            fn (mixed $cast): bool => is_string($cast) && explode(':', $cast, 2)[0] === self::class,
        ));
    }
}
