<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Backups (Spatie laravel-backup) ────────────────────────────────────────
Schedule::command('backup:clean')
    ->daily()
    ->at('01:00')
    ->onOneServer();

Schedule::command('backup:run')
    ->daily()
    ->at('01:30')
    ->onOneServer();

Schedule::command('backup:monitor')
    ->daily()
    ->at('07:00')
    ->onOneServer();

// ── Horizon ────────────────────────────────────────────────────────────────
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// ── Websites: renewal alerts + uptime pings ────────────────────────────────
Schedule::command('websites:check-renewals')
    ->dailyAt('08:00')
    ->onOneServer();

Schedule::command('websites:ping')
    ->everyFifteenMinutes()
    ->onOneServer()
    ->withoutOverlapping();

// ── Calendar: fallback sync (catches missed Cal.com webhooks) ──────────────
Schedule::command('calcom:sync')
    ->hourly()
    ->onOneServer();
