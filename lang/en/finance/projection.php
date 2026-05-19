<?php

return [
    'page_title' => 'Cash flow projection',
    'recompute' => 'Recompute',
    'derived_hint' => 'Derived from active recurring invoices and domain renewal schedules. No rows are created.',
    'empty' => 'Nothing projected in this window. Add a recurring invoice or set renewal_cost on a domain to see entries here.',

    'fields' => [
        'window' => 'Projection window',
    ],

    'windows' => [
        3 => '3 months',
        6 => '6 months',
        12 => '12 months',
        24 => '24 months',
    ],

    'sections' => [
        'totals' => 'Projected totals — next :n months',
        'monthly' => 'Month by month',
        'entries' => 'Projected line items',
    ],

    'totals' => [
        'income' => 'Projected income',
        'loss' => 'Projected loss',
        'net' => 'Projected net',
    ],

    'columns' => [
        'date' => 'Date',
        'description' => 'Description',
        'source' => 'Source',
        'amount' => 'Amount',
    ],

    'sources' => [
        'recurring_fattura' => 'Recurring invoice',
        'domain' => 'Domain renewal',
    ],

    'entries' => [
        'domain_renewal' => 'Renewal — :name',
    ],
];
