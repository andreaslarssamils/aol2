<?php

namespace App\Filament\Resources\Kommuner;

use App\Filament\Resources\Kommuner\Pages\ManageKommuner;
use App\Models\Kommun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Sveriges kommuner. Seedas från SCB och är skrivskyddade i panelen. */
class KommunResource extends Resource
{
    protected static ?string $model = Kommun::class;

    protected static ?string $slug = 'kommuner';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Plattform';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'kommun';

    protected static ?string $pluralModelLabel = 'kommuner';

    protected static ?string $recordTitleAttribute = 'namn';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('namn')
                    ->label('Namn')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kod')
                    ->label('SCB-kod')
                    ->searchable(),
                TextColumn::make('lan.namn')
                    ->label('Län')
                    ->sortable(),
                TextColumn::make('slug'),
                TextColumn::make('sites_count')
                    ->label('Sajter')
                    ->counts('sites'),
            ])
            ->filters([
                SelectFilter::make('lan_id')
                    ->label('Län')
                    ->relationship('lan', 'namn')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('namn')
            ->paginated([25, 50, 100]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageKommuner::route('/'),
        ];
    }
}
