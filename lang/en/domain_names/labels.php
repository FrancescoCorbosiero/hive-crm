<?php

return [
    'singular' => 'Domain',
    'plural' => 'Domains',

    'sections' => [
        'identity' => 'Domain',
        'renewal' => 'Renewal',
        'links' => 'Links',
        'links_hint' => 'Leave blank to auto-link: the website is matched from the URL, the customer from the website.',
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
