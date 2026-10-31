<?php

namespace App\Filament\Portal\Resources\Locations\Schemas;

use App\Filament\Forms\Components\OpeningHourExceptionsRepeater;
use App\Filament\Forms\Components\OpeningHoursRepeater;
use App\Filament\Portal\Schemas\Components\PendingRevisionBanner;
use App\Filament\Portal\Schemas\PortalFields;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PendingRevisionBanner::make(),
                Section::make('Adress')
                    ->description('Granskas av redaktionen innan ändringen publiceras.')
                    ->schema([
                        PortalFields::kommun()
                            ->required(),
                        TextInput::make('namn')
                            ->label('Lokalt namn')
                            ->maxLength(255)
                            ->placeholder(fn (): string => Filament::getTenant()?->namn ?? '')
                            ->helperText('Tomt = verksamhetens namn. Används t.ex. för "Acme Centrum".'),
                        TextInput::make('adress')
                            ->label('Adress')
                            ->maxLength(255),
                        TextInput::make('postnummer')
                            ->label('Postnummer')
                            ->maxLength(255),
                        TextInput::make('ort')
                            ->label('Ort')
                            ->maxLength(255),
                        TextInput::make('telefon')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('lat')
                            ->label('Latitud')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90),
                        TextInput::make('lng')
                            ->label('Longitud')
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180),
                        PortalFields::slug()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Öppettider')
                    ->description('Sparas direkt utan granskning. Flera rader per dag är tillåtet, t.ex. 11–14 och 17–21.')
                    ->schema([
                        OpeningHoursRepeater::make(),
                    ]),
                Section::make('Avvikande öppettider')
                    ->description('Sparas direkt utan granskning. Röda dagar och semesterstängt – avvikelsen vinner alltid över ordinarie tider för sitt datum.')
                    ->schema([
                        OpeningHourExceptionsRepeater::make(),
                    ])
                    ->collapsible(),
            ]);
    }
}
