<?php

return [
    'singular' => 'Scheduled task',
    'plural' => 'Scheduled tasks',

    'name' => 'Name',
    'command' => 'Artisan command',
    'command_help' => 'Pick from the whitelist of safe commands. System commands cannot be changed here.',
    'cron_expression' => 'Cron expression',
    'cron_help' => 'Standard 5-field cron syntax (minute hour day month weekday). Examples: "0 6 * * *" daily at 06:00, "*/15 * * * *" every 15 min.',
    'cron_invalid' => 'Not a valid cron expression.',
    'timezone' => 'Timezone',
    'description' => 'Description',
    'is_enabled' => 'Enabled',
    'is_enabled_short' => 'On',
    'is_system' => 'System task',
    'is_system_short' => 'System',
    'without_overlapping' => 'No overlap',
    'without_overlapping_help' => 'Skip a run if the previous one is still in progress.',
    'on_one_server' => 'Run on one server',
    'on_one_server_help' => 'In multi-server setups, run on a single node only.',

    'next_run' => 'Next run',
    'last_run' => 'Last run',
    'last_started_at' => 'Started at',
    'last_finished_at' => 'Finished at',
    'last_exit_code' => 'Exit code',
    'last_status' => 'Status',
    'last_output' => 'Output',
    'duration' => 'Duration',

    'status' => [
        'never' => 'Never run',
        'running' => 'Running',
        'success' => 'Success',
        'failure' => 'Failure',
    ],

    'run_now' => 'Run now',
    'run_now_done' => 'Task finished (exit :code).',
    'run_now_failed' => 'Run failed',
    'run_now_unknown' => 'Command is not in the whitelist — refusing to run.',
    'sync_system' => 'Sync system tasks',
    'sync_done' => 'System tasks synced.',

    'section' => [
        'task' => 'Task',
        'execution' => 'Execution options',
        'last_run' => 'Last run',
    ],
];
