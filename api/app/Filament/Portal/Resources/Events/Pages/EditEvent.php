<?php

namespace App\Filament\Portal\Resources\Events\Pages;

use App\Filament\Portal\Concerns\SubmitsRevisions;
use App\Filament\Portal\Resources\Events\EventResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditEvent extends EditRecord
{
    use SubmitsRevisions;

    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['categories'] = $this->getRecord()->categories()->pluck('categories.id')->all();

        return $data;
    }

    protected function revisionPayload(array $data, ?Model $record): array
    {
        // Platsen låses i formuläret; SubmitRevision stryper ändå ägarlänkar på befintliga rader.
        return [...$data, 'business_location_id' => $record?->business_location_id];
    }
}
