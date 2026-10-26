<?php

namespace App\Filament\Resources\Businesses\RelationManagers;

use App\Filament\Forms\Components\KommunSelect;
use App\Filament\Forms\Components\SlugInput;
use App\Filament\Infolists\Components\SlugHistoryEntry;
use App\Filament\Schemas\Components\LocaleTabs;
use App\Filament\Tables\Filters\KommunFilter;
use App\Models\BusinessLocation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Var verksamheten finns: en rad per adress med öppettider, avvikelser och slug per språk. */
class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';

    protected static ?string $title = 'Lokaliseringar';

    protected static ?string $modelLabel = 'lokalisering';

    protected static ?string $pluralModelLabel = 'lokaliseringar';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Adress')
                    ->schema([
                        KommunSelect::make()
                            ->required(),
                        TextInput::make('namn')
                            ->label('Lokalt namn')
                            ->maxLength(255)
                            ->placeholder(fn (): string => $this->getOwnerRecord()->namn)
                            ->helperText('Tomt = företagsnamnet. Används t.ex. för "Acme Centrum".'),
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
                            ->numeric(),
                        TextInput::make('lng')
                            ->label('Longitud')
                            ->numeric(),
                        Toggle::make('is_primary')
                            ->label('Primär lokalisering')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Bara en per verksamhet.'),
                    ])
                    ->columns(2),
                LocaleTabs::make()
                    ->localeSchema(fn (string $locale): array => [
                        SlugInput::forLocale($locale)
                            ->fallbackTo(fn (Get $get): string => $get('namn') ?: $this->getOwnerRecord()->namn),
                        SlugHistoryEntry::forLocale($locale),
                    ]),
                Section::make('Öppettider')
                    ->description('Flera rader per dag är tillåtet, t.ex. 11–14 och 17–21.')
                    ->schema([
                        Repeater::make('openingHours')
                            ->hiddenLabel()
                            ->relationship()
                            ->schema([
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
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('Lägg till tider'),
                    ]),
                Section::make('Avvikande öppettider')
                    ->description('Röda dagar och semesterstängt. Avvikelsen vinner alltid över ordinarie tider för sitt datum.')
                    ->schema([
                        Repeater::make('openingHourExceptions')
                            ->hiddenLabel()
                            ->relationship()
                            ->schema([
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
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->addActionLabel('Lägg till avvikelse'),
                    ])
                    ->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (BusinessLocation $record): string => $record->display_name)
            ->columns([
                TextColumn::make('display_name')
                    ->label('Namn'),
                TextColumn::make('kommun.namn')
                    ->label('Kommun')
                    ->sortable(),
                TextColumn::make('adress')
                    ->label('Adress')
                    ->searchable(),
                TextColumn::make('ort')
                    ->label('Ort')
                    ->searchable(),
                TextColumn::make('telefon')
                    ->label('Telefon')
                    ->toggleable(),
                IconColumn::make('is_primary')
                    ->label('Primär')
                    ->boolean(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->state(fn (BusinessLocation $record): ?string => $record->slugFor(LocaleTabs::defaultLocale()))
                    ->toggleable(),
            ])
            ->filters([
                KommunFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
