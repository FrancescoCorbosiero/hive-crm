<?php

return [
    'singular' => 'Financial entry',
    'plural' => 'Financial entries',

    'type' => [
        'income' => 'Income',
        'loss' => 'Loss',
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
        'recent_entries' => 'Recent entries',
        'ytd_income' => 'YTD income',
        'ytd_loss' => 'YTD loss',
        'ytd_net' => 'YTD net',
    ],

    'categories' => [
        'website_subscription' => 'Website subscription',
        'one_time_project' => 'One-off project',
        'consulting' => 'Consulting',
        'hosting' => 'Hosting',
        'domains' => 'Domains',
        'software' => 'Software',
        'tools' => 'Tools',
        'travel' => 'Travel',
        'taxes' => 'Taxes',
        'other' => 'Other',
    ],

    'actions' => [
        'generate_fattura' => 'Generate Fattura',
        'generate_fattura_success' => 'Fattura :number created from this entry.',
        'generate_fattura_failure' => 'Could not generate Fattura.',
    ],
];
