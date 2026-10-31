<?php

namespace App\Policies;

use App\Models\Business;
use Illuminate\Database\Eloquent\Model;

/** Evenemang hör till en verksamhet via sin lokalisering. Event utan plats i katalogen är redaktionens. */
class EventPolicy extends BusinessContentPolicy
{
    protected function businessOf(Model $record): ?Business
    {
        return $record->businessLocation?->business;
    }
}
