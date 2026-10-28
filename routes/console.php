<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Every scheduled job in the app lives as a row in scheduled_tasks
// (managed via the Filament resource at /admin/scheduled-tasks).
// ScheduleLoader binds the enabled rows into Laravel's Scheduler when the
// scheduler is resolved — see SchedulingServiceProvider.
//
// To add a new schedulable command:
//   1. Register the artisan signature in SchedulingServiceProvider's
//      registerSystemCommands() (this is the security whitelist).
//   2. Run `php artisan scheduling:sync` to seed the row.
//   3. Tune cron / enabled flag from the UI.
