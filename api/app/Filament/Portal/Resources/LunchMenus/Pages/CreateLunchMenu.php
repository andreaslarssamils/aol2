<?php

namespace App\Filament\Portal\Resources\LunchMenus\Pages;

use App\Enums\ContentStatus;
use App\Filament\Portal\Concerns\SubmitsRevisions;
use App\Filament\Portal\Resources\LunchMenus\LunchMenuResource;
use App\Filament\Portal\Resources\LunchMenus\Pages\Concerns\CopiesPreviousWeek;
use App\Filament\Portal\Schemas\PortalFields;
use App\Models\LunchMenu;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/** Med auto_approve skapas menyn direkt som publicerad; annars som utkast med en väntande revision. */
class CreateLunchMenu extends CreateRecord
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

    protected function handleRecordCreation(array $data): Model
    {
        $payload = LunchMenuResource::normalizeMenu($data);

        // Formuläret begränsar valet till verksamhetens lokaliseringar; här kontrolleras det igen innan något skrivs.
        if (! array_key_exists($payload['business_location_id'] ?? null, PortalFields::tenantLocations())) {
            throw ValidationException::withMessages(['data.business_location_id' => 'Lokaliseringen hör inte till verksamheten.']);
        }

        if (! LunchMenuResource::savesDirectly()) {
            return $this->submitRevision(new LunchMenu, $payload);
        }

        $this->savedDirectly = true;

        return LunchMenu::create([
            ...$payload,
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);
    }

    protected function getCreatedNotification(): ?Notification
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
