<?php

namespace App\Filament\Portal\Resources\LunchMenus\Tables;

use App\Filament\Portal\Schemas\PortalFields;
use App\Models\LunchMenu;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LunchMenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['businessLocation.business', 'latestPendingRevision']))
            ->recordTitle(fn (LunchMenu $record): string => $record->revisionTitle())
            ->columns([
                TextColumn::make('week')
                    ->label('Vecka')
                    ->formatStateUsing(fn (LunchMenu $record): string => "v. {$record->week} {$record->iso_year}")
                    ->sortable(['iso_year', 'week']),
                TextColumn::make('businessLocation.display_name')
                    ->label('Lokalisering'),
                TextColumn::make('dishes')
                    ->label('Rätter')
                    ->state(fn (LunchMenu $record): int => collect($record->menu ?? [])->flatten()->filter()->count()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                IconColumn::make('pending')
                    ->label('Väntar på granskning')
                    ->boolean()
                    ->state(fn (LunchMenu $record): bool => $record->latestPendingRevision !== null)
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                TextColumn::make('updated_at')
                    ->label('Uppdaterad')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('business_location_id')
                    ->label('Lokalisering')
                    ->options(fn (): array => PortalFields::tenantLocations()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('iso_year')->orderByDesc('week'))
            ->emptyStateHeading('Ingen lunchmeny än')
            ->emptyStateDescription('Lägg in veckans rätter – gästerna ser dem på lokaliseringens sida.');
    }
}
