<?php

namespace App\Policies;

use App\Models\Business;
use Illuminate\Database\Eloquent\Model;

class LunchMenuPolicy extends BusinessContentPolicy
{
    protected function businessOf(Model $record): ?Business
    {
        return $record->businessLocation?->business;
    }
}
