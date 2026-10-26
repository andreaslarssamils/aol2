<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Select;

/** Kommunval mot relationen "kommun". Byt relation med ->relationship() där kolumnen heter något annat. */
class KommunSelect extends Select
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
