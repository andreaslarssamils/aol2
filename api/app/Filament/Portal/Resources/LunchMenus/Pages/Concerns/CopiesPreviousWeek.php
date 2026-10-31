<?php

namespace App\Filament\Portal\Resources\LunchMenus\Pages\Concerns;

use App\Filament\Portal\Resources\LunchMenus\LunchMenuResource;
use App\Models\LunchMenu;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** "Kopiera förra veckan": fyller rätterna från lokaliseringens meny för veckan före formulärets vecka. */
trait CopiesPreviousWeek
{
    protected function copyPreviousWeekAction(): Action
    {
        return Action::make('copyPreviousWeek')
            ->label('Kopiera förra veckan')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->action(function (): void {
                $state = $this->form->getRawState();
                $locationId = $state['business_location_id'] ?? $this->getRecord()?->business_location_id;
                $year = (int) ($state['iso_year'] ?? 0);
                $week = (int) ($state['week'] ?? 0);

                if (! $locationId || $year < 2000 || $week < 1 || $week > 53) {
                    Notification::make()
                        ->warning()
                        ->title('Välj lokalisering, år och vecka först')
                        ->send();

                    return;
                }

                $previous = CarbonImmutable::now()->setISODate($year, $week)->subWeek();

                $source = LunchMenuResource::getEloquentQuery()
                    ->where('business_location_id', $locationId)
                    ->where('iso_year', $previous->isoWeekYear)
                    ->where('week', $previous->isoWeek)
                    ->first();

                if (! $source instanceof LunchMenu) {
                    Notification::make()
                        ->warning()
                        ->title("Det finns ingen lunchmeny för v. {$previous->isoWeek} {$previous->isoWeekYear}")
                        ->send();

                    return;
                }

                $this->form->fill([...$state, 'menu' => $source->menu]);

                Notification::make()
                    ->success()
                    ->title("Rätterna från v. {$previous->isoWeek} är kopierade")
                    ->body('Spara när du justerat dem.')
                    ->send();
            });
    }
}
