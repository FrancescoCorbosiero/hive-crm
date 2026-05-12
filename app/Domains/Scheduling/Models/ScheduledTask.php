<?php

declare(strict_types=1);

namespace App\Domains\Scheduling\Models;

use Cron\CronExpression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One row = one registration with Laravel's Scheduler.
 *
 * Rows flagged is_system represent schedules that ship with the app
 * (backups, recurring invoices, etc.). They can be disabled and their
 * cron expression edited from the UI, but they cannot be deleted —
 * removing them would silently break the platform.
 *
 * The actual binding into Laravel's Scheduler happens once per cron
 * tick in routes/console.php via ScheduleLoader::register(). Runtime
 * outcomes (last_started_at / last_finished_at / last_exit_code /
 * last_output) are written back by the lifecycle callbacks the loader
 * attaches to each scheduled event.
 */
class ScheduledTask extends Model
{
    protected $table = 'scheduled_tasks';

    protected $fillable = [
        'name',
        'command',
        'cron_expression',
        'timezone',
        'description',
        'is_system',
        'is_enabled',
        'without_overlapping',
        'on_one_server',
        'last_started_at',
        'last_finished_at',
        'last_exit_code',
        'last_output',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_enabled' => 'boolean',
            'without_overlapping' => 'boolean',
            'on_one_server' => 'boolean',
            'last_started_at' => 'datetime',
            'last_finished_at' => 'datetime',
            'last_exit_code' => 'integer',
        ];
    }

    public function isValidCron(): bool
    {
        return CronExpression::isValidExpression($this->cron_expression);
    }

    public function nextRunAt(): ?Carbon
    {
        if (! $this->is_enabled || ! $this->isValidCron()) {
            return null;
        }

        $tz = $this->timezone ?: config('app.timezone');
        $next = (new CronExpression($this->cron_expression))->getNextRunDate('now', 0, false, $tz);

        return Carbon::instance($next);
    }

    public function lastStatus(): string
    {
        if ($this->last_started_at === null) {
            return 'never';
        }

        if ($this->last_finished_at === null) {
            return 'running';
        }

        return $this->last_exit_code === 0 ? 'success' : 'failure';
    }

    public function durationSeconds(): ?int
    {
        if ($this->last_started_at && $this->last_finished_at) {
            return $this->last_started_at->diffInSeconds($this->last_finished_at);
        }

        return null;
    }
}
