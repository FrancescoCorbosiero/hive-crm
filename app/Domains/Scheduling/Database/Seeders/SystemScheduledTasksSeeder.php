<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Database\Seeders;

use App\Domains\Scheduling\Models\ScheduledTask;
use App\Domains\Scheduling\Registry\ScheduledCommandRegistry;
use Illuminate\Database\Seeder;

/**
 * Seeds the scheduled_tasks table with every system-default command
 * registered in ScheduledCommandRegistry. Idempotent — re-running it
 * inserts only the rows that don't already exist (matched by command
 * signature), so it's safe to call on every migration / deploy.
 */
class SystemScheduledTasksSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ScheduledCommandRegistry::systemDefaults() as $command => $meta) {
            ScheduledTask::query()->firstOrCreate(
                ['command' => $command],
                [
                    'name' => $meta['label'],
                    'cron_expression' => $meta['default_cron'],
                    'description' => $meta['description'],
                    'is_system' => true,
                    'is_enabled' => true,
                    'without_overlapping' => true,
                    'on_one_server' => true,
                ],
            );
        }
    }
}
