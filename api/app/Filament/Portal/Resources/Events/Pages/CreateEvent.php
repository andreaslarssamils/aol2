<?php

namespace App\Filament\Portal\Resources\Events\Pages;

use App\Filament\Portal\Concerns\SubmitsRevisions;
use App\Filament\Portal\Resources\Events\EventResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** Nytt evenemang blir ett utkast på verksamhetens lokalisering tills revisionen godkänns. */
class CreateEvent extends CreateRecord
{
    use SubmitsRevisions;

    protected static string $resource = EventResource::class;

    protected function revisionPayload(array $data, ?Model $record): array
    {
        return [...$data, 'created_by' => Filament::auth()->id()];
    }
}
