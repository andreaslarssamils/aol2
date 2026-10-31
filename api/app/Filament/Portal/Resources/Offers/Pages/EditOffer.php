<?php

namespace App\Filament\Portal\Resources\Offers\Pages;

use App\Filament\Portal\Concerns\SubmitsRevisions;
use App\Filament\Portal\Resources\Offers\OfferResource;
use Filament\Resources\Pages\EditRecord;

class EditOffer extends EditRecord
{
    use SubmitsRevisions;

    protected static string $resource = OfferResource::class;
}
