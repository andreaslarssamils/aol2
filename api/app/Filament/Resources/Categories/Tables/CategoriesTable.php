<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Enums\CategoryType;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Tables\Columns\TranslatableTextColumn;
use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('parent'))
            ->columns([
                TranslatableTextColumn::make('namn')
                    ->label('Namn'),
                TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('parent')
                    ->label('Överordnad')
                    ->state(fn (Category $record): ?string => $record->parent ? CategoryForm::nameOf($record->parent) : null)
                    ->placeholder('–'),
                TextColumn::make('sort_order')
                    ->label('Sortering')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Typ')
                    ->options(CategoryType::class),
                SelectFilter::make('parent_id')
                    ->label('Överordnad')
                    ->relationship('parent', 'slug')
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => CategoryForm::nameOf($record))
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order');
    }
}
