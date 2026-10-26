<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Kanonisk slug för en locale på en HasSlugs-modell. Läser från slugs-tabellen och sparar via
 * setSlug() – aldrig direkt – så att den gamla sluggen blir 301-historik vid byte. Lämnas fältet
 * tomt genereras sluggen från fallback-fältet (t.ex. titeln för samma språk).
 */
class SlugInput extends TextInput
{
    protected string $locale;

    protected string|Closure|null $fallback = null;

    public static function forLocale(string $locale): static
    {
        return static::make("slugs.{$locale}")->locale($locale);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Slug');
        $this->maxLength(255);
        $this->placeholder('Genereras från titeln om tomt');
        $this->helperText('Byter du slug behålls den gamla som omdirigering.');

        // Ingen modellkolumn: sluggen bor i slugs-tabellen och går via setSlug().
        $this->dehydrated(false);

        $this->loadStateFromRelationshipsUsing(static function (SlugInput $component, Model $record): void {
            $component->state($record->slugFor($component->getLocale()));
        });

        $this->saveRelationshipsUsing(static function (SlugInput $component, Model $record, Get $get): void {
            $slug = $component->normalize($component->getState())
                ?? $component->normalize($component->getFallbackValue($get));

            if ($slug === null) {
                return;
            }

            $record->setSlug($component->getLocale(), $slug);
        });
    }

    public function locale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /** Fält (punktnotation) eller closure som ger texten sluggen genereras från när fältet är tomt. */
    public function fallbackTo(string|Closure $fallback): static
    {
        $this->fallback = $fallback;

        return $this;
    }

    protected function getFallbackValue(Get $get): ?string
    {
        if ($this->fallback === null) {
            return null;
        }

        $value = $this->fallback instanceof Closure
            ? $this->evaluate($this->fallback)
            : $get($this->fallback);

        return is_string($value) ? $value : null;
    }

    protected function normalize(?string $value): ?string
    {
        $slug = Str::of($value ?? '')->slug()->limit(255, '')->rtrim('-')->toString();

        return $slug === '' ? null : $slug;
    }
}
