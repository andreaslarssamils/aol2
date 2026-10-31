<?php

namespace App\Filament\Portal\Resources\Revisions\Tables;

use App\Enums\RevisionableType;
use App\Enums\RevisionStatus;
use App\Filament\Actions\RevisionDiffAction;
use App\Models\Revision;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RevisionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Inskickad')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('revisionable_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RevisionableType::tryFrom($state)?->getLabel() ?? $state),
                TextColumn::make('title')
                    ->label('Innehåll')
                    ->state(fn (Revision $record): string => $record->revisionable?->revisionTitle() ?? 'Raderat innehåll'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('review_comment')
                    ->label('Redaktionens kommentar')
                    ->placeholder('–')
                    ->wrap(),
                TextColumn::make('reviewed_at')
                    ->label('Avgjord')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('–')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(RevisionStatus::class),
                SelectFilter::make('revisionable_type')
                    ->label('Typ')
                    ->options(RevisionableType::class),
            ])
            ->recordActions([
                RevisionDiffAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Inga ändringar än')
            ->emptyStateDescription('Allt du skickar in för granskning hamnar här med status och redaktionens kommentar.');
    }
}
