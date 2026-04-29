<?php

return [
    'singular' => 'Transaction',
    'plural' => 'Transactions',

    'type' => [
        'income' => 'Income',
        'expense' => 'Expense',
    ],

    'fields' => [
        'type' => 'Type',
        'amount' => 'Amount',
        'occurred_at' => 'Date',
        'description' => 'Description',
        'category' => 'Category',
        'source_type' => 'Source',
        'source_id' => 'Reference',
        'contact' => 'Contact',
        'notes' => 'Notes',
    ],

    'sections' => [
        'overview' => 'Overview',
        'attribution' => 'Attribution',
        'extras' => 'Notes',
    ],

    'widgets' => [
        'monthly_income' => 'Monthly income (last 12 months)',
        'recent_transactions' => 'Recent transactions',
        'ytd_income' => 'YTD income',
        'ytd_expense' => 'YTD expense',
        'ytd_net' => 'YTD net',
    ],

    'categories' => [
        'website_subscription' => 'Website subscription',
        'one_time_project' => 'One-off project',
        'consulting' => 'Consulting',
        'hosting' => 'Hosting',
        'software' => 'Software',
        'tools' => 'Tools',
        'travel' => 'Travel',
        'taxes' => 'Taxes',
        'other' => 'Other',
    ],
];
