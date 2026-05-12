<?php

declare(strict_types=1);

namespace App\Domains\Scheduling;

use App\Domains\Scheduling\Database\Seeders\SystemScheduledTasksSeeder;
use App\Domains\Scheduling\Registry\ScheduledCommandRegistry;
use App\Domains\Scheduling\Services\ScheduleLoader;
use Illuminate\Support\ServiceProvider;

class SchedulingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ScheduleLoader::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        $this->registerSystemCommands();

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\SyncScheduledTasksCommand::class,
            ]);
        }
    }

    /**
     * Catalog of artisan commands that may be scheduled from the UI.
     * Adding to this list is the single security gate — the Filament
     * form only accepts signatures present here, and ScheduleLoader
     * silently skips DB rows whose command is no longer whitelisted.
     *
     * The is_system entries are seeded into the scheduled_tasks table
     * by SystemScheduledTasksSeeder on install / migration.
     */
    private function registerSystemCommands(): void
    {
        ScheduledCommandRegistry::register(
            command: 'backup:clean',
            label: 'Backup: clean old archives',
            description: 'Removes backup archives older than the retention window.',
            defaultCron: '0 1 * * *',
            isSystem: true,
        );
        ScheduledCommandRegistry::register(
            command: 'backup:run',
            label: 'Backup: run',
            description: 'Creates a fresh database + files backup.',
            defaultCron: '30 1 * * *',
            isSystem: true,
        );
        ScheduledCommandRegistry::register(
            command: 'backup:monitor',
            label: 'Backup: monitor health',
            description: 'Verifies the latest backup completed within the threshold.',
            defaultCron: '0 7 * * *',
            isSystem: true,
        );
        ScheduledCommandRegistry::register(
            command: 'horizon:snapshot',
            label: 'Horizon: metrics snapshot',
            description: 'Records queue throughput metrics for Horizon dashboards.',
            defaultCron: '*/5 * * * *',
            isSystem: true,
        );
        ScheduledCommandRegistry::register(
            command: 'websites:check-renewals',
            label: 'Websites: renewal alerts',
            description: 'Notifies owners of websites with expiring domains / hosting.',
            defaultCron: '0 8 * * *',
            isSystem: true,
        );
        ScheduledCommandRegistry::register(
            command: 'websites:ping',
            label: 'Websites: uptime ping',
            description: 'Pings every active website and records uptime metrics.',
            defaultCron: '*/15 * * * *',
            isSystem: true,
        );
        ScheduledCommandRegistry::register(
            command: 'calcom:sync',
            label: 'Calendar: Cal.com fallback sync',
            description: 'Re-syncs Cal.com bookings to cover missed webhooks.',
            defaultCron: '0 * * * *',
            isSystem: true,
        );
        ScheduledCommandRegistry::register(
            command: 'fatture:issue-recurring',
            label: 'Fatture: issue recurring',
            description: 'Issues new fatture for every recurring schedule due today.',
            defaultCron: '0 6 * * *',
            isSystem: true,
        );
        ScheduledCommandRegistry::register(
            command: 'fatture:recompute-overdue',
            label: 'Fatture: recompute overdue',
            description: 'Flips past-due unpaid fatture to Overdue status.',
            defaultCron: '15 6 * * *',
            isSystem: true,
        );
    }
}
