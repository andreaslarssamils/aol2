<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gemensam bas för innehållets form requests. Reglerna används av SubmitRevision,
 * som validerar en revisions payload mot dem, och av kommande API-endpoints.
 */
abstract class ContentRequest extends FormRequest
{
    /** Behörighet avgörs av anroparen (policy, portal eller granskningskö), inte av requesten. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regler för ett översättningsbart json-fält, {"sv": ..., "en": ...}. Krävs fältet
     * måste standardspråket vara ifyllt; övriga språk är valfria. Okända språk avvisas.
     *
     * @return array<string, array<int, string>>
     */
    protected function translatable(string $field, bool $required = false, ?int $maxLength = null): array
    {
        $locales = static::locales();
        $defaultLocale = $locales[0];

        $rules = [$field => [$required ? 'required' : 'nullable', 'array:'.implode(',', $locales)]];

        foreach ($locales as $locale) {
            $rules["{$field}.{$locale}"] = array_values(array_filter([
                $required && $locale === $defaultLocale ? 'required' : 'nullable',
                'string',
                $maxLength !== null ? "max:{$maxLength}" : null,
            ]));
        }

        return $rules;
    }

    /**
     * Slug per språk. Appliceras via HasSlugs::setSlug() vid godkännande, aldrig som kolumn.
     *
     * @return array<string, array<int, string>>
     */
    protected function slugs(): array
    {
        return [
            'slugs' => ['nullable', 'array:'.implode(',', static::locales())],
            'slugs.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return list<string> */
    protected static function locales(): array
    {
        return array_keys(config('app.locales'));
    }
}
