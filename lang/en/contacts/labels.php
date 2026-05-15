<?php

return [
    'singular' => 'Contact',
    'plural' => 'Contacts',

    'name' => 'Name',
    'ragione_sociale' => 'Company name',
    'email' => 'Email',
    'phone' => 'Phone',
    'vat_number' => 'VAT number',
    'tax_code' => 'Tax code',
    'sdi_code' => 'SDI / Recipient code',
    'sdi_code_help' => '7-character code for Italian e-invoicing (FatturaPA).',
    'pec_email' => 'PEC email',
    'roles' => 'Roles',
    'notes' => 'Notes',
    'do_not_email' => 'Do not email',
    'do_not_email_short' => 'Email',
    'do_not_email_help' => 'When on, this contact is skipped by every campaign.',
    'trello_board_url' => 'Trello board URL',
    'trello_board_url_short' => 'Trello',
    'trello_open' => 'Open Trello',
    'updated_at' => 'Updated',

    'address' => [
        'street' => 'Street',
        'city' => 'City',
        'province' => 'Province',
        'postal_code' => 'Postal code',
        'country' => 'Country',
    ],

    'section' => [
        'identity' => 'Identity',
        'tax' => 'Tax info',
        'address' => 'Address',
        'preferences' => 'Preferences',
        'links' => 'Links',
        'links_hint' => "External links tied to this contact, such as the customer's Trello board.",
    ],

    'summary' => [
        'quotations' => 'Open quotations',
        'fatture' => 'Unpaid fatture',
        'calendar' => 'Upcoming events',
        'calendar_empty' => 'No upcoming events.',
        'mail' => 'Recent emails',
        'mail_empty' => 'No recent emails.',
        'mail_status' => 'Status',
        'sent_at' => 'Sent',
        'open' => 'Open',
        'notes' => 'Notes',
    ],
];
