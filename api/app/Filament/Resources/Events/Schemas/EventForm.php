<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\CategoryType;
use App\Filament\Forms\Components\ContentStatusSelect;
use App\Filament\Forms\Components\ImagesUpload;
use App\Filament\Forms\Components\KommunSelect;
use App\Filament\Forms\Components\SlugInput;
use App\Filament\Infolists\Components\SlugHistoryEntry;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Schemas\Components\LocaleTabs;
use App\Models\BusinessLocation;
use App\Models\Category;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Evenemang')
                    ->schema([
                        TextInput::make('namn')
                            ->label('Namn')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('business_location_id')
                            ->label('Arrangör/plats i katalogen')
                            ->relationship('businessLocation', 'id')
                            ->getOptionLabelFromRecordUsing(fn (BusinessLocation $record): string => static::locationLabel($record))
                            ->getSearchResultsUsing(fn (string $search): array => static::searchLocations($search))
                            ->searchable()
                            ->live()
                            ->helperText('Lämna tomt och ange plats i fritext om arrangören inte finns i katalogen.'),
                        KommunSelect::make()
                            ->required(fn (Get $get): bool => blank($get('business_location_id')))
                            ->helperText('Fylls i från platsen om tom. Skrivs aldrig över.'),
                        TextInput::make('venue_name')
                            ->label('Plats (fritext)')
                            ->maxLength(255),
                        TextInput::make('adress')
                            ->label('Adress')
                            ->maxLength(255),
                        TextInput::make('lat')
                            ->label('Latitud')
                            ->numeric(),
                        TextInput::make('lng')
                            ->label('Longitud')
                            ->numeric(),
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
                        TextInput::make('recurrence_rule')
                            ->label('Återkommande (RRULE)')
                            ->maxLength(255)
                            ->placeholder('FREQ=WEEKLY;BYDAY=TU')
                            ->helperText('Förekomsterna materialiseras som egna rader i ett senare steg.')
                            ->columnSpanFull(),
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
                        Select::make('categories')
                            ->label('Kategorier')
                            ->multiple()
                            ->relationship(
                                name: 'categories',
                                titleAttribute: 'slug',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('type', CategoryType::Event)
                                    ->orderBy('sort_order'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Category $record): string => CategoryForm::nameOf($record))
                            ->preload()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                LocaleTabs::make()
                    ->localeSchema(fn (string $locale): array => [
                        SlugInput::forLocale($locale)
                            ->fallbackTo('namn'),
                        SlugHistoryEntry::forLocale($locale),
                        RichEditor::make("beskrivning.{$locale}")
                            ->label('Beskrivning'),
                    ]),
                Section::make('Publicering')
                    ->schema([
                        ContentStatusSelect::make(),
                        DateTimePicker::make('published_at')
                            ->label('Publicerad')
                            ->seconds(false),
                    ])
                    ->columns(2),
                Section::make('Bilder')
                    ->schema([
                        ImagesUpload::make(),
                    ]),
            ]);
    }

    public static function locationLabel(BusinessLocation $location): string
    {
        return "{$location->display_name} – {$location->kommun?->namn}";
    }

    /** @return array<int, string> */
    protected static function searchLocations(string $search): array
    {
        return BusinessLocation::query()
            ->with(['business', 'kommun'])
            ->where(fn (Builder $query) => $query
                ->where('namn', 'ilike', "%{$search}%")
                ->orWhere('ort', 'ilike', "%{$search}%")
                ->orWhereHas('business', fn (Builder $business) => $business->where('namn', 'ilike', "%{$search}%")))
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (BusinessLocation $location): array => [$location->id => static::locationLabel($location)])
            ->all();
    }
}
