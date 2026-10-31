<?php

namespace App\Filament\Portal\Resources\Events\Tables;

use App\Models\Event;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['businessLocation.business', 'kommun', 'latestPendingRevision']))
            ->columns([
                TextColumn::make('namn')
                    ->label('Namn')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('businessLocation.display_name')
                    ->label('Plats'),
                TextColumn::make('kommun.namn')
                    ->label('Kommun'),
                TextColumn::make('starts_at')
                    ->label('Börjar')
                    ->dateTime('Y-m-d H:i', config('app.display_timezone'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                IconColumn::make('pending')
                    ->label('Väntar på granskning')
                    ->boolean()
                    ->state(fn (Event $record): bool => $record->latestPendingRevision !== null)
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('starts_at', 'desc')
            ->emptyStateHeading('Inga evenemang')
            ->emptyStateDescription('Skapa ett evenemang så syns det i kalendern när redaktionen godkänt det.');
    }
}
