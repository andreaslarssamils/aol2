<?php

namespace App\Filament\Portal\Resources\LunchMenus\Pages;

use App\Enums\ContentStatus;
use App\Filament\Portal\Concerns\SubmitsRevisions;
use App\Filament\Portal\Resources\LunchMenus\LunchMenuResource;
use App\Filament\Portal\Resources\LunchMenus\Pages\Concerns\CopiesPreviousWeek;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/** Med auto_approve skrivs menyn direkt och publiceras; annars via revision som allt annat. */
class EditLunchMenu extends EditRecord
{
    use CopiesPreviousWeek, SubmitsRevisions;

    protected static string $resource = LunchMenuResource::class;

    protected bool $savedDirectly = false;

    protected function getHeaderActions(): array
    {
        return [
            $this->copyPreviousWeekAction(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $payload = LunchMenuResource::normalizeMenu($data);

        if (! LunchMenuResource::savesDirectly()) {
            return $this->submitRevision($record, $payload);
        }

        $record->update([
            ...$payload,
            'status' => ContentStatus::Published,
            'published_at' => $record->published_at ?? now(),
        ]);

        $this->savedDirectly = true;

        return $record;
    }

    protected function getSavedNotification(): ?Notification
    {
        if (! $this->savedDirectly) {
            return $this->submissionNotification();
        }

        return Notification::make()
            ->success()
            ->title('Publicerad')
            ->body('Lunchmenyn sparas direkt eftersom verksamhetens ändringar godkänns automatiskt.');
    }
}
