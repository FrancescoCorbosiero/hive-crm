<?php

use App\Domains\Scheduling\Database\Seeders\SystemScheduledTasksSeeder;
use App\Domains\Scheduling\Models\ScheduledTask;
use App\Domains\Scheduling\Registry\ScheduledCommandRegistry;

it('seeds every system command exactly once', function () {
    (new SystemScheduledTasksSeeder())->run();
    $first = ScheduledTask::query()->count();

    (new SystemScheduledTasksSeeder())->run();
    $second = ScheduledTask::query()->count();

    expect($first)->toBe(count(ScheduledCommandRegistry::systemDefaults()));
    expect($second)->toBe($first);
});

it('flags seeded tasks as system + enabled', function () {
    (new SystemScheduledTasksSeeder())->run();

    $task = ScheduledTask::query()->where('command', 'fatture:recompute-overdue')->first();

    expect($task)->not->toBeNull();
    expect($task->is_system)->toBeTrue();
    expect($task->is_enabled)->toBeTrue();
    expect($task->isValidCron())->toBeTrue();
});

it('rejects malformed cron expressions', function () {
    $task = new ScheduledTask([
        'name' => 'broken',
        'command' => 'backup:run',
        'cron_expression' => 'not-a-cron',
    ]);

    expect($task->isValidCron())->toBeFalse();
    expect($task->nextRunAt())->toBeNull();
});

it('reports lastStatus based on persisted run timestamps', function () {
    $task = new ScheduledTask(['name' => 'x', 'command' => 'backup:run', 'cron_expression' => '* * * * *']);
    expect($task->lastStatus())->toBe('never');

    $task->last_started_at = now();
    expect($task->lastStatus())->toBe('running');

    $task->last_finished_at = now();
    $task->last_exit_code = 0;
    expect($task->lastStatus())->toBe('success');

    $task->last_exit_code = 1;
    expect($task->lastStatus())->toBe('failure');
});

it('whitelist refuses unknown commands', function () {
    expect(ScheduledCommandRegistry::has('backup:run'))->toBeTrue();
    expect(ScheduledCommandRegistry::has('migrate:fresh'))->toBeFalse();
    expect(ScheduledCommandRegistry::has('tinker'))->toBeFalse();
});
