<?php

namespace App\Filament\Portal\Resources\Locations;

use App\Filament\Portal\Resources\Locations\Pages\CreateLocation;
use App\Filament\Portal\Resources\Locations\Pages\EditLocation;
use App\Filament\Portal\Resources\Locations\Pages\ListLocations;
use App\Filament\Portal\Resources\Locations\Schemas\LocationForm;
use App\Filament\Portal\Resources\Locations\Tables\LocationsTable;
use App\Models\BusinessLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Verksamhetens adresser. Adressuppgifterna går via revision; öppettider och avvikelser
 * sparas direkt. Scopas på tenant via relationen business.
 */
class LocationResource extends Resource
{
    protected static ?string $model = BusinessLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'lokalisering';

    protected static ?string $pluralModelLabel = 'lokaliseringar';

    public static function form(Schema $schema): Schema
    {
        return LocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }
}
