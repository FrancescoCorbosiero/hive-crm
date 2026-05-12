<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Services;

use App\Domains\Scheduling\Models\ScheduledTask;
use App\Domains\Scheduling\Registry\ScheduledCommandRegistry;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Throwable;

/**
 * Binds DB-stored scheduled_tasks rows into Laravel's Scheduler.
 *
 * Called from routes/console.php on every artisan boot. The Schedule
 * facade is consulted once per minute by schedule:run, which means
 * adding / disabling a task from the UI takes effect on the next tick
 * without needing a deploy.
 *
 * The output of each run is streamed to storage/app/scheduled_tasks/
 * via sendOutputTo(), then read back in the after() callback and
 * stored (truncated to 5 KB) on the task row so it stays inspectable
 * from the Filament resource.
 */
class ScheduleLoader
{
    public const OUTPUT_DIR = 'scheduled_tasks';

    public const OUTPUT_LIMIT = 5000;

    public function register(): void
    {
        // Guard against running before the migration has been applied —
        // for example on the very first `php artisan migrate` invocation.
        try {
            $tasks = ScheduledTask::query()->where('is_enabled', true)->get();
        } catch (Throwable $e) {
            return;
        }

        File::ensureDirectoryExists(storage_path('app/'.self::OUTPUT_DIR));

        foreach ($tasks as $task) {
            if (! ScheduledCommandRegistry::has($task->command)) {
                // Whitelist enforcement: a row whose command is no longer
                // recognised is silently skipped rather than crashing the
                // scheduler. Operator can re-register or fix the row.
                continue;
            }

            if (! $task->isValidCron()) {
                continue;
            }

            $this->bind($task);
        }
    }

    private function bind(ScheduledTask $task): void
    {
        /** @var Event $event */
        $event = Schedule::command($task->command)
            ->cron($task->cron_expression)
            ->name('scheduled_task_'.$task->id);

        if ($task->timezone) {
            $event->timezone($task->timezone);
        }
        if ($task->without_overlapping) {
            $event->withoutOverlapping();
        }
        if ($task->on_one_server) {
            $event->onOneServer();
        }

        $outputPath = storage_path('app/'.self::OUTPUT_DIR.'/'.$task->id.'.log');
        $event->sendOutputTo($outputPath);

        $taskId = $task->id;

        $event->before(function () use ($taskId, $outputPath): void {
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }
            ScheduledTask::query()->whereKey($taskId)->update([
                'last_started_at' => now(),
                'last_finished_at' => null,
                'last_exit_code' => null,
                'last_output' => null,
            ]);
        });

        $event->onSuccess(function () use ($taskId, $outputPath): void {
            self::recordResult($taskId, exitCode: 0, outputPath: $outputPath);
        });

        $event->onFailure(function () use ($taskId, $outputPath): void {
            self::recordResult($taskId, exitCode: 1, outputPath: $outputPath);
        });
    }

    private static function recordResult(int $taskId, int $exitCode, string $outputPath): void
    {
        $output = null;
        if (file_exists($outputPath)) {
            $contents = (string) file_get_contents($outputPath);
            $output = mb_substr($contents, -self::OUTPUT_LIMIT);
        }

        try {
            ScheduledTask::query()->whereKey($taskId)->update([
                'last_finished_at' => now(),
                'last_exit_code' => $exitCode,
                'last_output' => $output,
            ]);
        } catch (Throwable $e) {
            Log::error('scheduling.task_result_write_failed', [
                'task_id' => $taskId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
