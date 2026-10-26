<?php

namespace App\Observers;

use App\Models\BusinessLocation;
use App\Models\Event;

class EventObserver
{
    /** Evenemangets kommun följer alltid lokaliseringen när en sådan är satt. */
    public function saving(Event $event): void
    {
        if ($event->business_location_id === null) {
            return;
        }

        $kommunId = BusinessLocation::withTrashed()
            ->whereKey($event->business_location_id)
            ->value('kommun_id');

        if ($kommunId !== null) {
            $event->kommun_id = $kommunId;
        }
    }
}
