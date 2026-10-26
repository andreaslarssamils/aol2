<?php

namespace App\Observers;

use App\Models\BusinessLocation;
use App\Models\Event;

class EventObserver
{
    /**
     * Fyll i kommun från lokaliseringen när ingen kommun är satt.
     * Skriver aldrig över en befintlig kommun.
     */
    public function saving(Event $event): void
    {
        if ($event->kommun_id !== null || $event->business_location_id === null) {
            return;
        }

        $event->kommun_id = BusinessLocation::withTrashed()
            ->whereKey($event->business_location_id)
            ->value('kommun_id');
    }
}
