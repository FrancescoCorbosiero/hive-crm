<?php

return [
    'singular' => 'Website',
    'plural' => 'Websites',

    'name' => 'Name',
    'url' => 'URL',
    'status' => 'Status',
    'owner_contact' => 'Client',
    'tech_stack' => 'Tech stack',
    'notes' => 'Notes',
    'subscription_started_at' => 'Subscription start',
    'next_renewal_at' => 'Next renewal',
    'renewal_period_months' => 'Renewal period (months)',
    'days_until_renewal' => 'Days until renewal',
    'trello_board_url' => 'Trello board URL',
    'trello_board_url_short' => 'Trello',
    'trello_open' => 'Open Trello',

    'section' => [
        'general' => 'General',
        'subscription' => 'Subscription',
        'tech' => 'Tech stack',
        'register_cost' => 'Register setup cost',
        'register_cost_hint' => 'Log the setup or first-cycle hosting cost as an expense. Tagged so it can\'t be logged twice for the same website.',
    ],

    'cost' => [
        'toggle' => 'Log setup cost in the ledger',
        'amount' => 'Cost amount',
        'paid_at' => 'Paid on',
        'method' => 'Payment method',
    ],

    'auto_populate' => [
        'setup_description' => 'Website setup / hosting — :name',
        'payment_method_note' => 'Paid via :method',
    ],

    'widgets' => [
        'upcoming_renewals' => 'Upcoming renewals',
        'no_upcoming_renewals' => 'No renewals in the next 30 days',
        'down_websites' => 'Offline websites',
        'no_down_websites' => 'All active sites are reachable.',
    ],

    'ping' => [
        'is_up' => 'Online',
        'last_status_code' => 'Last HTTP',
        'last_pinged_at' => 'Last check',
        'never' => 'Never checked',
    ],
];
