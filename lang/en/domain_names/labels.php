<?php

return [
    'singular' => 'Domain',
    'plural' => 'Domains',

    'sections' => [
        'identity' => 'Domain',
        'renewal' => 'Renewal',
        'links' => 'Links',
        'links_hint' => 'Leave blank to auto-link: the website is matched from the URL, the customer from the website.',
        'register_payment' => 'Register payment',
        'register_payment_hint' => 'Log the registration cost as an expense in your ledger. Tagged so it can\'t be logged twice for the same domain.',
        'create_website' => 'Create website',
        'create_website_hint' => 'Spin up the sibling Website row and link it back to this domain in one shot.',
        'extras' => 'Notes',
    ],

    'fields' => [
        'name' => 'Domain name',
        'registrar' => 'Registrar',
        'status' => 'Status',
        'registered_at' => 'Registered on',
        'expires_at' => 'Expires on',
        'renewal_period_months' => 'Renewal period (months)',
        'auto_renew' => 'Auto-renew',
        'renewal_cost' => 'Renewal cost',
        'owner_contact' => 'Customer',
        'website' => 'Website',
        'notes' => 'Notes',
        'days_left' => 'Days to expiry',
        'register_payment_enabled' => 'Log registration cost in the ledger',
        'registration_cost' => 'Registration cost',
        'registration_paid_at' => 'Paid on',
        'registration_method' => 'Payment method',
        'create_website_enabled' => 'Create the matching website',
        'create_website_helper' => 'Skipped automatically if a website with this domain already exists.',
        'new_website_url' => 'Website URL',
        'new_website_name' => 'Website name',
        'new_website_cost' => 'Website setup cost',
        'new_website_cost_helper' => 'Leave blank to skip logging the cost. Otherwise a LOSS entry is created in Finance, idempotent per website.',
        'new_website_paid_at' => 'Website paid on',
        'new_website_method' => 'Website payment method',
    ],

    'auto_populate' => [
        'registration_description' => 'Domain registration :name (:registrar)',
        'payment_method_note' => 'Paid via :method',
    ],

    'auto_link_placeholder' => 'Automatic',

    'filters' => [
        'expiring_soon' => 'Expiring soon (30 days)',
        'expired' => 'Expired',
    ],

    'actions' => [
        'log_renewal' => 'Log renewal',
        'log_renewal_hint' => 'Creates an expense entry for the renewal and rolls the expiry date forward by one period.',
        'log_renewal_description' => 'Domain renewal :name (:registrar)',
        'log_renewal_success' => 'Renewal logged and expiry updated.',
        'log_renewal_already' => 'Renewal already logged for this cycle.',
        'log_renewal_no_cost' => 'Set the domain renewal cost first.',
    ],

    'widgets' => [
        'expiring' => 'Expiring or expired domains — :days-day window',
        'no_expiring' => 'No domains expiring soon.',
        'expired_badge' => ':days days overdue',
        'total' => 'Total domains',
        'active_count' => ':count active',
        'expiring_30' => 'Expiring (30 days)',
        'expired_count' => ':count already expired',
        'none_expired' => 'None expired',
        'annual_cost' => 'Annual renewal cost',
        'annual_cost_hint' => 'Normalised to 12 months, active domains.',
    ],
];
