<?php

namespace App\Filament\Portal\Resources\Locations\Pages;

use App\Filament\Portal\Concerns\SubmitsRevisions;
use App\Filament\Portal\Resources\Locations\LocationResource;
use Filament\Resources\Pages\EditRecord;

/** Adressfälten går via SubmitRevision; öppettidsrepeatrarna sparar sina rader direkt. */
class EditLocation extends EditRecord
{
    use SubmitsRevisions;

    protected static string $resource = LocationResource::class;
}
