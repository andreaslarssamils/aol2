<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Ordinarie öppettider mot relationen openingHours: en rad per veckodag och intervall, flera
 * intervall per dag är tillåtet (11–14 och 17–21). Sparas direkt av Filament, utan revision –
 * faktauppgifter med låg risk.
 */
class OpeningHoursRepeater extends Repeater
{
    /** @var array<int, string> weekday enligt OpeningHour: 0 = måndag ... 6 = söndag */
    public const array WEEKDAYS = [
        0 => 'Måndag',
        1 => 'Tisdag',
        2 => 'Onsdag',
        3 => 'Torsdag',
        4 => 'Fredag',
        5 => 'Lördag',
        6 => 'Söndag',
    ];

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'openingHours');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();
        $this->relationship();
        $this->schema([
            Select::make('weekday')
                ->label('Veckodag')
                ->options(self::WEEKDAYS)
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
                ->live()
                ->inline(false),
        ]);
        $this->columns(4);
        $this->defaultItems(0);
        $this->addActionLabel('Lägg till tider');
    }
}
