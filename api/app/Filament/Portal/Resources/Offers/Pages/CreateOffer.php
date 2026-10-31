<?php

namespace App\Filament\Portal\Resources\Offers\Pages;

use App\Filament\Portal\Concerns\SubmitsRevisions;
use App\Filament\Portal\Resources\Offers\OfferResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/** Nytt erbjudande blir ett utkast under aktuell verksamhet tills revisionen godkänns. */
class CreateOffer extends CreateRecord
{
    use SubmitsRevisions;

    protected static string $resource = OfferResource::class;

    protected function revisionPayload(array $data, ?Model $record): array
    {
        return [...$data, 'business_id' => Filament::getTenant()->getKey()];
    }
}
