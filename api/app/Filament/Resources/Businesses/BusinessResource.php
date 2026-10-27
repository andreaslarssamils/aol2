<?php

namespace App\Filament\Resources\Businesses;

use App\Filament\RelationManagers\RevisionsRelationManager;
use App\Filament\Resources\Businesses\Pages\CreateBusiness;
use App\Filament\Resources\Businesses\Pages\EditBusiness;
use App\Filament\Resources\Businesses\Pages\ListBusinesses;
use App\Filament\Resources\Businesses\RelationManagers\LocationsRelationManager;
use App\Filament\Resources\Businesses\RelationManagers\UsersRelationManager;
use App\Filament\Resources\Businesses\Schemas\BusinessForm;
use App\Filament\Resources\Businesses\Tables\BusinessesTable;
use App\Models\Business;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

/** Företag och föreningar. Adresser, öppettider och URL:er bor på lokaliseringarna; ägare i business_user. */
class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'verksamhet';

    protected static ?string $pluralModelLabel = 'verksamheter';

    protected static ?string $recordTitleAttribute = 'namn';

    public static function form(Schema $schema): Schema
    {
        return BusinessForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BusinessesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LocationsRelationManager::class,
            UsersRelationManager::class,
            RevisionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBusinesses::route('/'),
            'create' => CreateBusiness::route('/create'),
            'edit' => EditBusiness::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
