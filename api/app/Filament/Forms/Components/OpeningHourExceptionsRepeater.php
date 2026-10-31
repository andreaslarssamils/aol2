<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

/** Avvikande öppettider (röda dagar, semester) mot relationen openingHourExceptions. Sparas direkt, utan revision. */
class OpeningHourExceptionsRepeater extends Repeater
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'openingHourExceptions');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();
        $this->relationship();
        $this->schema([
            DatePicker::make('date')
                ->label('Datum')
                ->required(),
            TimePicker::make('opens')
                ->label('Öppnar')
                ->seconds(false)
                ->required(fn (Get $get): bool => ! $get('closed')),
            TimePicker::make('closes')
                ->label('Stänger')
                ->seconds(false)
                ->required(fn (Get $get): bool => ! $get('closed')),
            Toggle::make('closed')
                ->label('Stängt')
                ->default(true)
                ->live()
                ->inline(false),
            TextInput::make('note')
                ->label('Notering')
                ->maxLength(255)
                ->placeholder('Midsommarafton'),
        ]);
        $this->columns(5);
        $this->defaultItems(0);
        $this->addActionLabel('Lägg till avvikelse');
    }
}
