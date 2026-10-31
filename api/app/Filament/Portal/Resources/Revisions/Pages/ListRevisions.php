<?php

namespace App\Filament\Portal\Resources\Revisions\Pages;

use App\Filament\Portal\Resources\Revisions\RevisionResource;
use Filament\Resources\Pages\ListRecords;

class ListRevisions extends ListRecords
{
    protected static string $resource = RevisionResource::class;

    protected static ?string $title = 'Mina ändringar';
}
