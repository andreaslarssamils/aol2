<?php

namespace App\Filament\Tables\Columns;

use App\Filament\Schemas\Components\LocaleTabs;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Visar standardspråkets värde ur en översättningsbar json-kolumn och söker/sorterar på det. */
class TranslatableTextColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $locale = LocaleTabs::defaultLocale();

        $this->state(fn (Model $record): mixed => data_get($record->getAttribute($this->getName()), $locale));

        $this->searchable(query: fn (Builder $query, string $search): Builder => $query
            ->where("{$this->getName()}->{$locale}", 'ilike', "%{$search}%"));

        $this->sortable(query: fn (Builder $query, string $direction): Builder => $query
            ->orderBy("{$this->getName()}->{$locale}", $direction));
    }
}
