<?php

namespace App\Filament\Infolists\Components;

use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Model;

/** Tidigare (icke-kanoniska) slugs för en locale – de omdirigeras till den nuvarande. Visas bara vid redigering. */
class SlugHistoryEntry extends TextEntry
{
    protected string $locale;

    public static function forLocale(string $locale): static
    {
        return static::make("slug_history_{$locale}")->locale($locale);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Tidigare slugs (omdirigeras hit)');
        $this->badge();
        $this->color('gray');
        $this->placeholder('Inga tidigare slugs');
        $this->hiddenOn('create');

        $this->state(static function (SlugHistoryEntry $component, ?Model $record): array {
            if (! $record?->exists) {
                return [];
            }

            return $record->slugs()
                ->where('locale', $component->getLocale())
                ->where('is_canonical', false)
                ->latest('id')
                ->pluck('slug')
                ->all();
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
}
