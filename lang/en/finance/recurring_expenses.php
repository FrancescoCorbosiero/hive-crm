<?php

return [
    'singular' => 'Recurring expense',
    'plural' => 'Recurring expenses',

    'sections' => [
        'overview' => 'Details',
        'schedule' => 'Schedule',
        'extras' => 'Notes',
    ],

    'fields' => [
        'name' => 'Name',
        'frequency' => 'Frequency',
        'amount' => 'Amount',
        'category' => 'Category',
        'vendor' => 'Vendor',
        'started_at' => 'Started on',
        'next_due_at' => 'Next due',
        'last_logged_at' => 'Last logged',
        'is_active' => 'Active',
        'notes' => 'Notes',
    ],

    'actions' => [
        'log_occurrence' => 'Log occurrence',
        'log_occurrence_hint' => 'Create a loss entry for the next due date and advance the schedule by one period.',
        'log_occurrence_success' => 'Occurrence logged and next due date advanced.',
    ],
];
