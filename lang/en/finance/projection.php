<?php

return [
    'page_title' => 'Cash flow projection',
    'recompute' => 'Recompute',
    'derived_hint' => 'Derived from recurring invoices, unpaid invoices, recurring expenses and domain / hosting renewals. Overdue items count in the current month. No rows are created.',
    'empty' => 'Nothing projected in this window. Add a recurring invoice or expense, or set a renewal cost on a domain or website, to see entries here.',
    'overdue' => 'overdue',

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
        'invoice' => 'Unpaid invoice',
        'recurring_expense' => 'Recurring expense',
        'domain' => 'Domain renewal',
        'website' => 'Website renewal',
    ],
];
