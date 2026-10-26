<?php

namespace App\Filament\Resources\Promotions\Tables;

use App\Enums\PromotionPlacement;
use App\Enums\PromotionStatus;
use App\Filament\Schemas\Components\LocaleTabs;
use App\Filament\Tables\Filters\KommunFilter;
use App\Models\Article;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\Offer;
use App\Models\Promotion;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PromotionsTable
{
    /** @var array<string, string> morph-alias => etikett */
    protected const array TYPE_LABELS = [
        'event' => 'Evenemang',
        'article' => 'Artikel',
        'business_location' => 'Verksamhet',
        'offer' => 'Erbjudande',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['promotable', 'kommun']))
            ->columns([
                TextColumn::make('promotable_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::TYPE_LABELS[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('promotable_title')
                    ->label('Innehåll')
                    ->state(fn (Promotion $record): ?string => static::promotableTitle($record->promotable))
                    ->limit(50),
                TextColumn::make('kommun.namn')
                    ->label('Visas i')
                    ->sortable(),
                TextColumn::make('placement')
                    ->label('Yta')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Från')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Till')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('Tills vidare')
                    ->sortable(),
                TextColumn::make('priority')
                    ->label('Prio')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_paid')
                    ->label('Betald')
                    ->boolean(),
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
                    ->options(PromotionStatus::class),
                SelectFilter::make('placement')
                    ->label('Yta')
                    ->options(PromotionPlacement::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function promotableTitle(?Model $promotable): ?string
    {
        $locale = LocaleTabs::defaultLocale();

        return match (true) {
            $promotable instanceof Event => $promotable->namn,
            $promotable instanceof Article => $promotable->titel[$locale] ?? null,
            $promotable instanceof BusinessLocation => $promotable->display_name,
            $promotable instanceof Offer => $promotable->titel[$locale] ?? null,
            default => null,
        };
    }
}
