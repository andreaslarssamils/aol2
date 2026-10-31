<?php

namespace App\Filament\Resources\Claims\Tables;

use App\Enums\ClaimStatus;
use App\Models\BusinessClaim;
use App\Notifications\BusinessClaimDecided;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClaimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['business.primaryLocation.kommun', 'user', 'handler']))
            ->columns([
                TextColumn::make('business.namn')
                    ->label('Verksamhet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('business.primaryLocation.kommun.namn')
                    ->label('Kommun')
                    ->placeholder('–'),
                TextColumn::make('user.name')
                    ->label('Sökande')
                    ->description(fn (BusinessClaim $record): ?string => $record->user?->email)
                    ->searchable(),
                TextColumn::make('message')
                    ->label('Meddelande')
                    ->placeholder('–')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (ClaimStatus $state): string => match ($state) {
                        ClaimStatus::Pending => 'warning',
                        ClaimStatus::Approved => 'success',
                        ClaimStatus::Rejected => 'danger',
                    }),
                TextColumn::make('created_at')
                    ->label('Inskickat')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('handler.name')
                    ->label('Avgjort av')
                    ->placeholder('–')
                    ->toggleable(),
                TextColumn::make('handled_at')
                    ->label('Avgjort')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('–')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ClaimStatus::class)
                    ->default(ClaimStatus::Pending->value),
            ])
            ->recordActions([
                static::approveAction(),
                static::rejectAction(),
            ])
            ->defaultSort('created_at')
            ->emptyStateHeading('Inga anspråk')
            ->emptyStateDescription('När en ägare begär att ta över sin verksamhet i portalen hamnar anspråket här.');
    }

    protected static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Godkänn')
            ->icon(Heroicon::OutlinedCheck)
            ->color('success')
            ->visible(fn (BusinessClaim $record): bool => $record->status === ClaimStatus::Pending)
            ->requiresConfirmation()
            ->modalHeading(fn (BusinessClaim $record): string => "Godkänn anspråket på {$record->business->namn}?")
            ->modalDescription(fn (BusinessClaim $record): string => "{$record->user->name} blir ägare i portalen och verksamheten hanteras därefter av verksamheten själv. Den sökande får ett mejl.")
            ->modalSubmitActionLabel('Godkänn')
            ->successNotificationTitle('Anspråket är godkänt')
            ->action(function (BusinessClaim $record, Action $action): void {
                $record->approve(auth()->user());
                $record->user->notify(new BusinessClaimDecided($record));

                $action->success();
            });
    }

    protected static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Avslå')
            ->icon(Heroicon::OutlinedXMark)
            ->color('danger')
            ->visible(fn (BusinessClaim $record): bool => $record->status === ClaimStatus::Pending)
            ->requiresConfirmation()
            ->modalHeading(fn (BusinessClaim $record): string => "Avslå anspråket på {$record->business->namn}?")
            ->modalDescription('Verksamheten rörs inte. Den sökande får ett mejl om beslutet.')
            ->modalSubmitActionLabel('Avslå')
            ->successNotificationTitle('Anspråket är avslaget')
            ->action(function (BusinessClaim $record, Action $action): void {
                $record->reject(auth()->user());
                $record->user->notify(new BusinessClaimDecided($record));

                $action->success();
            });
    }
}
