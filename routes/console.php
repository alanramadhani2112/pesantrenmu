<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('banding:check-deadlines')->daily();
Schedule::command('perbaikan:check-deadlines')->daily();
Schedule::command('reminders:asesor2')->daily();
Schedule::command('akreditasi:check-deadlines')->daily();
Schedule::command('trash:purge')->daily();

// Task 13.3: Scheduled commands for perbaikan deadline enforcement (Req 4.9, 4.10)
Schedule::command('akreditasi:check-perbaikan-deadlines')->daily();
Schedule::command('akreditasi:send-perbaikan-reminders')->daily();
