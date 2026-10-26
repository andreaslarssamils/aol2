<?php

namespace App\Filament\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;

/** Listfilter på kommun via relationen "kommun". */
class KommunFilter extends SelectFilter
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'kommun_id');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Kommun');
        $this->relationship('kommun', 'namn');
        $this->searchable();
        $this->preload();
    }
}
