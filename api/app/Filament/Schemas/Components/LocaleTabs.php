<?php

namespace App\Filament\Schemas\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;

/**
 * En flik per innehållsspråk (config('app.locales')). Fälten i varje flik adresserar sin
 * locale med punktnotation ("titel.sv") så att json-kolumnerna sparas som {"sv": ..., "en": ...}.
 */
class LocaleTabs extends Tabs
{
    public static function make(string|Htmlable|Closure|null $label = null): static
    {
        return parent::make($label ?? 'Språk');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpanFull();
    }

    /**
     * @param  Closure(string $locale, bool $isDefault): array<Component>  $schema
     */
    public function localeSchema(Closure $schema): static
    {
        $defaultLocale = static::defaultLocale();

        $this->tabs(collect(static::locales())
            ->map(fn (string $label, string $locale): Tab => Tab::make($locale)
                ->label($label)
                ->schema($schema($locale, $locale === $defaultLocale)))
            ->values()
            ->all());

        return $this;
    }

    /** @return array<string, string> locale => språknamn */
    public static function locales(): array
    {
        return config('app.locales');
    }

    /** Redaktionens standardspråk – det första i listan. */
    public static function defaultLocale(): string
    {
        return array_key_first(static::locales());
    }
}
