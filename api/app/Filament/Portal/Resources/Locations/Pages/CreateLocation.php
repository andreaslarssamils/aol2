<?php

namespace App\Filament\Portal\Resources\Locations\Pages;

use App\Filament\Portal\Concerns\SubmitsRevisions;
use App\Filament\Portal\Resources\Locations\LocationResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** Ny lokalisering blir ett utkast under aktuell verksamhet tills revisionen godkänns. Öppettiderna sparas på utkastet direkt. */
class CreateLocation extends CreateRecord
{
    use SubmitsRevisions;

    protected static string $resource = LocationResource::class;

    protected function revisionPayload(array $data, ?Model $record): array
    {
        $tenant = Filament::getTenant();

        return [
            ...$data,
            'business_id' => $tenant->getKey(),
            // Bara en primär per verksamhet (unikt index) – första lokaliseringen blir primär.
            'is_primary' => ! $tenant->locations()->where('is_primary', true)->exists(),
        ];
    }
}
