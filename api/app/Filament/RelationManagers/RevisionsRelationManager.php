<?php

namespace App\Filament\RelationManagers;

use App\Models\Revision;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

/** Versionshistorik: godkända revisioner med vem, när och kommentar. Skrivskyddad – historiken ändras aldrig. */
class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    protected static ?string $title = 'Revisioner';

    protected static ?string $modelLabel = 'revision';

    protected static ?string $pluralModelLabel = 'revisioner';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->approved()->with(['submitter', 'reviewer']))
            ->columns([
                TextColumn::make('applied_at')
                    ->label('Genomförd')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('submitter.name')
                    ->label('Inskickad av')
                    ->placeholder('–'),
                TextColumn::make('reviewer.name')
                    ->label('Godkänd av')
                    ->placeholder('–'),
                TextColumn::make('reviewed_at')
                    ->label('Godkänd')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('review_comment')
                    ->label('Kommentar')
                    ->placeholder('–')
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('payload')
                    ->label('Visa')
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (Revision $record): string => 'Version '.$record->applied_at?->format('Y-m-d H:i'))
                    ->modalDescription('Nuvarande rad jämförd med vad den här versionen innehöll.')
                    ->modalContent(fn (Revision $record): View => view('filament.revisions.diff', [
                        'rows' => $record->diff(),
                        'proposedLabel' => 'Denna version',
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Stäng')
                    ->modalWidth(Width::FourExtraLarge),
            ])
            ->defaultSort('applied_at', 'desc')
            ->emptyStateHeading('Inga godkända revisioner')
            ->emptyStateDescription('Ändringar som godkänts i granskningskön hamnar här.');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
