<?php

namespace App\Filament\Portal\Resources\Events\Schemas;

use App\Enums\CategoryType;
use App\Filament\Forms\Components\PendingImagesUpload;
use App\Filament\Forms\Components\RichText;
use App\Filament\Portal\Schemas\Components\PendingRevisionBanner;
use App\Filament\Portal\Schemas\PortalFields;
use App\Filament\Schemas\Components\LocaleTabs;
use App\Models\BusinessLocation;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                PendingRevisionBanner::make(),
                Section::make('Evenemang')
                    ->schema([
                        TextInput::make('namn')
                            ->label('Namn')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        PortalFields::tenantLocation()
                            ->label('Plats i katalogen')
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $kommunId = BusinessLocation::query()->whereKey($state)->value('kommun_id');

                                if ($kommunId !== null) {
                                    $set('kommun_id', $kommunId);
                                }
                            })
                            ->disabledOn('edit')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create')
                            ->helperText('Evenemanget visas på lokaliseringens sida.'),
                        PortalFields::kommun()
                            ->required()
                            ->helperText('Fylls i från platsen – ändra om evenemanget hålls i en annan kommun.'),
                        TextInput::make('venue_name')
                            ->label('Plats (fritext)')
                            ->maxLength(255)
                            ->placeholder('Stora scenen'),
                        TextInput::make('adress')
                            ->label('Adress')
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
                    ])
                    ->columns(2),
                Section::make('Tid')
                    ->schema([
                        Toggle::make('all_day')
                            ->label('Heldag')
                            ->inline(false),
                        DateTimePicker::make('starts_at')
                            ->label('Börjar')
                            ->required()
                            ->seconds(false),
                        DateTimePicker::make('ends_at')
                            ->label('Slutar')
                            ->seconds(false)
                            ->afterOrEqual('starts_at'),
                    ])
                    ->columns(3),
                Section::make('Biljetter och kategorier')
                    ->schema([
                        TextInput::make('pris')
                            ->label('Pris')
                            ->maxLength(255)
                            ->placeholder('Fri entré'),
                        TextInput::make('ticket_url')
                            ->label('Biljettlänk')
                            ->url()
                            ->maxLength(255),
                        PortalFields::categories(CategoryType::Event),
                    ])
                    ->columns(2),
                LocaleTabs::make()
                    ->localeSchema(fn (string $locale): array => [
                        RichText::make("beskrivning.{$locale}")
                            ->label('Beskrivning'),
                    ]),
                Section::make('Bilder')
                    ->schema([
                        PendingImagesUpload::make(),
                    ]),
            ]);
    }
}
