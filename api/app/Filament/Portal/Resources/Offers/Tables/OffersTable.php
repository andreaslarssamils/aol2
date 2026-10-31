<?php

namespace App\Filament\Portal\Resources\Offers\Tables;

use App\Filament\Tables\Columns\TranslatableTextColumn;
use App\Models\Offer;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('latestPendingRevision'))
            ->columns([
                TranslatableTextColumn::make('titel')
                    ->label('Titel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('discount_type')
                    ->label('Rabatt')
                    ->badge()
                    ->placeholder('–'),
                TextColumn::make('starts_at')
                    ->label('Från')
                    ->date('Y-m-d')
                    ->placeholder('–')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Till')
                    ->date('Y-m-d')
                    ->placeholder('–')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                IconColumn::make('pending')
                    ->label('Väntar på granskning')
                    ->boolean()
                    ->state(fn (Offer $record): bool => $record->latestPendingRevision !== null)
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Inga erbjudanden')
            ->emptyStateDescription('Skapa ett erbjudande så kan redaktionen lyfta fram det på sajten.');
    }
}
