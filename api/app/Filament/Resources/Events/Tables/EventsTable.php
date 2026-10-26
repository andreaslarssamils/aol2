<?php

namespace App\Filament\Resources\Events\Tables;

use App\Enums\CategoryType;
use App\Enums\ContentStatus;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Tables\Filters\KommunFilter;
use App\Models\Category;
use App\Models\Event;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('categories'))
            ->columns([
                TextColumn::make('namn')
                    ->label('Namn')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('kommun.namn')
                    ->label('Kommun')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Börjar')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('categories')
                    ->label('Kategorier')
                    ->badge()
                    ->state(fn (Event $record): array => $record->categories
                        ->map(fn (Category $category): string => CategoryForm::nameOf($category))
                        ->all()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
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
                SelectFilter::make('categories')
                    ->label('Kategori')
                    ->relationship(
                        'categories',
                        'slug',
                        fn (Builder $query): Builder => $query->where('type', CategoryType::Event)->orderBy('sort_order'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => CategoryForm::nameOf($record))
                    ->multiple()
                    ->preload(),
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
