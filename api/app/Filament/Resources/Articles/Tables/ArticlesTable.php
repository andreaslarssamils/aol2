<?php

namespace App\Filament\Resources\Articles\Tables;

use App\Enums\ContentStatus;
use App\Filament\Tables\Columns\TranslatableTextColumn;
use App\Filament\Tables\Filters\KommunFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TranslatableTextColumn::make('titel')
                    ->label('Titel')
                    ->limit(60),
                TextColumn::make('kommun.namn')
                    ->label('Kommun')
                    ->sortable(),
                TextColumn::make('author_name')
                    ->label('Byline')
                    ->toggleable(),
                IconColumn::make('featured')
                    ->label('Utvald')
                    ->boolean(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Publicerad')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Skapad')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                KommunFilter::make(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContentStatus::class),
                TernaryFilter::make('featured')
                    ->label('Utvald'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
