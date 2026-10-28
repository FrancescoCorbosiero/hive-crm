<?php

use App\Domains\Scheduling\Database\Seeders\SystemScheduledTasksSeeder;
use App\Domains\Scheduling\Filament\Resources\ScheduledTaskResource\Pages\EditScheduledTask;
use App\Domains\Scheduling\Models\ScheduledTask;
use App\Domains\Scheduling\Registry\ScheduledCommandRegistry;
use App\Models\User;
use Livewire\Livewire;

it('seeds every system command exactly once', function () {
    (new SystemScheduledTasksSeeder)->run();
    $first = ScheduledTask::query()->count();

    (new SystemScheduledTasksSeeder)->run();
    $second = ScheduledTask::query()->count();

    expect($first)->toBe(count(ScheduledCommandRegistry::systemDefaults()));
    expect($second)->toBe($first);
});

it('flags seeded tasks as system + enabled', function () {
    (new SystemScheduledTasksSeeder)->run();

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

it('reports the last run duration in whole seconds', function () {
    // Carbon 3's diffInSeconds() is a float (5.0 here): returning it from
    // ?int threw a TypeError, so the edit page of any task that had run
    // crashed.
    $task = new ScheduledTask([
        'last_started_at' => '2026-05-01 03:00:00',
        'last_finished_at' => '2026-05-01 03:00:05',
    ]);

    expect($task->durationSeconds())->toBe(5);
});

it('opens the edit page of a task that has already run', function () {
    $this->actingAs(User::factory()->create());
    $task = ScheduledTask::query()->create([
        'name' => 'Backups',
        'command' => 'backup:run',
        'cron_expression' => '0 3 * * *',
        'last_started_at' => now()->subMinutes(2),
        'last_finished_at' => now()->subMinute(),
        'last_exit_code' => 0,
    ]);

    Livewire::test(EditScheduledTask::class, ['record' => $task->id])
        ->assertSuccessful()
        ->assertSee('60s');
});
