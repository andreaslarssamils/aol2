<?php

namespace App\Filament\Resources\Kommuner\Pages;

use App\Filament\Resources\Kommuner\KommunResource;
use Filament\Resources\Pages\ManageRecords;

class ManageKommuner extends ManageRecords
{
    protected static string $resource = KommunResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
