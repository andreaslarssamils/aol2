<?php

namespace App\Filament\Portal\Resources\Locations\Tables;

use App\Models\BusinessLocation;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['business', 'kommun', 'latestPendingRevision']))
            ->recordTitle(fn (BusinessLocation $record): string => $record->display_name)
            ->columns([
                TextColumn::make('display_name')
                    ->label('Namn'),
                TextColumn::make('kommun.namn')
                    ->label('Kommun'),
                TextColumn::make('adress')
                    ->label('Adress'),
                TextColumn::make('ort')
                    ->label('Ort'),
                TextColumn::make('telefon')
                    ->label('Telefon')
                    ->toggleable(),
                IconColumn::make('is_primary')
                    ->label('Primär')
                    ->boolean(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                IconColumn::make('pending')
                    ->label('Väntar på granskning')
                    ->boolean()
                    ->state(fn (BusinessLocation $record): bool => $record->latestPendingRevision !== null)
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('is_primary', 'desc')
            ->emptyStateHeading('Inga lokaliseringar')
            ->emptyStateDescription('Lägg till adressen där verksamheten finns.');
    }
}
