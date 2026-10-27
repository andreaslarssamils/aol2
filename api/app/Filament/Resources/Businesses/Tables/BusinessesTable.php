<?php

namespace App\Filament\Resources\Businesses\Tables;

use App\Enums\BusinessKind;
use App\Enums\CategoryType;
use App\Enums\ContentStatus;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Models\Business;
use App\Models\Category;
use App\Models\Kommun;
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

class BusinessesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['categories', 'primaryLocation.kommun']))
            ->columns([
                TextColumn::make('namn')
                    ->label('Namn')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kind')
                    ->label('Typ')
                    ->badge()
                    ->sortable(),
                TextColumn::make('primaryLocation.kommun.namn')
                    ->label('Kommun')
                    ->placeholder('–'),
                TextColumn::make('categories')
                    ->label('Kategorier')
                    ->badge()
                    ->state(fn (Business $record): array => $record->categories
                        ->map(fn (Category $category): string => CategoryForm::nameOf($category))
                        ->all()),
                TextColumn::make('managed_by')
                    ->label('Hanteras av')
                    ->badge()
                    ->toggleable(),
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
                // Verksamheten har ingen egen kommun – filtrera på lokaliseringarnas.
                SelectFilter::make('kommun')
                    ->label('Kommun')
                    ->options(fn (): array => Kommun::query()->orderBy('namn')->pluck('namn', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, int|string $kommunId): Builder => $query->inKommun((int) $kommunId),
                    )),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ContentStatus::class),
                SelectFilter::make('categories')
                    ->label('Kategori')
                    ->relationship(
                        'categories',
                        'slug',
                        fn (Builder $query): Builder => $query->where('type', CategoryType::Business)->orderBy('sort_order'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => CategoryForm::nameOf($record))
                    ->multiple()
                    ->preload(),
                SelectFilter::make('kind')
                    ->label('Typ')
                    ->options(BusinessKind::class),
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
