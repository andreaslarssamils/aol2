<?php

namespace App\Filament\Portal\Resources\LunchMenus\Pages;

use App\Filament\Portal\Resources\LunchMenus\LunchMenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLunchMenus extends ListRecords
{
    protected static string $resource = LunchMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
