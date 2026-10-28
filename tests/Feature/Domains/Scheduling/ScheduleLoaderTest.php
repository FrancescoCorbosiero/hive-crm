<?php

use App\Domains\Scheduling\Models\ScheduledTask;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

function aScheduledTask(array $attributes = []): ScheduledTask
{
    return ScheduledTask::query()->create([
        'name' => 'Backups',
        'command' => 'backup:run',
        'cron_expression' => '30 1 * * *',
        'is_enabled' => true,
        ...$attributes,
    ]);
}

function resolveFreshSchedule(): Schedule
{
    app()->forgetInstance(Schedule::class);

    return app(Schedule::class);
}

it('binds the enabled, whitelisted, valid tasks when the scheduler is resolved', function () {
    $valid = aScheduledTask();
    aScheduledTask(['name' => 'Off', 'is_enabled' => false]);
    aScheduledTask(['name' => 'Not whitelisted', 'command' => 'migrate:fresh']);
    aScheduledTask(['name' => 'Broken', 'cron_expression' => 'not-a-cron']);

    $bound = collect(resolveFreshSchedule()->events())->map(fn (Event $event) => $event->description);

    expect($bound->all())->toBe(['scheduled_task_'.$valid->id]);
});

it('leaves scheduled_tasks alone on ordinary artisan commands', function () {
    aScheduledTask();
    // Boot a console kernel that has not loaded routes/console.php yet,
    // as every real artisan process does: the test app's kernel loaded it
    // during setUp, before the in-memory database was migrated.
    app()->forgetInstance(ConsoleKernel::class);
    DB::enableQueryLog();

    $this->artisan('inspire')->assertSuccessful();

    expect(collect(DB::getQueryLog())->pluck('query')->filter(fn (string $sql) => str_contains($sql, 'scheduled_tasks')))
        ->toBeEmpty();
});

it('records the exit code a run actually ended with', function (int $exitCode) {
    $task = aScheduledTask();
    $event = collect(resolveFreshSchedule()->events())->sole();

    $event->callBeforeCallbacks(app());
    $event->finish(app(), $exitCode);

    expect($task->fresh()->last_exit_code)->toBe($exitCode);
    expect($task->fresh()->last_finished_at)->not->toBeNull();
})->with([0, 1, 2, 127]);
