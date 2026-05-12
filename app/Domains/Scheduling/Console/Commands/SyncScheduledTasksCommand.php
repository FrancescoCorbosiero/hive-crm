<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Console\Commands;

use App\Domains\Scheduling\Database\Seeders\SystemScheduledTasksSeeder;
use Illuminate\Console\Command;

/**
 * Idempotent: inserts any system-default scheduled task whose command
 * signature is not yet in the scheduled_tasks table. Existing rows are
 * left untouched — operator edits (cron expression, enabled flag) are
 * never overwritten.
 *
 * Run after a deploy that added a new system schedule, or manually
 * when bootstrapping a fresh database.
 */
class SyncScheduledTasksCommand extends Command
{
    protected $signature = 'scheduling:sync';

    protected $description = 'Insert missing system-default rows into scheduled_tasks (idempotent).';

    public function handle(): int
    {
        $before = \App\Domains\Scheduling\Models\ScheduledTask::query()->count();
        (new SystemScheduledTasksSeeder())->run();
        $after = \App\Domains\Scheduling\Models\ScheduledTask::query()->count();

        $this->info(sprintf('Scheduled tasks: %d → %d (%+d).', $before, $after, $after - $before));

        return self::SUCCESS;
    }
}
