<?php

use App\Jobs\PruneOrphanMedia;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ägaruppladdningar som aldrig blev godkända städas varje natt.
Schedule::job(new PruneOrphanMedia)->daily();
