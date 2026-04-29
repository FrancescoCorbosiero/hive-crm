<?php

return [
    'singular' => 'Lead',
    'plural' => 'Leads',

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'source' => 'Source',
        'status' => 'Status',
        'estimated_value' => 'Estimated value',
        'next_action_at' => 'Next action',
        'notes' => 'Notes',
        'converted_contact' => 'Created contact',
        'converted_at' => 'Converted at',
    ],

    'sections' => [
        'identity' => 'Identity',
        'pipeline' => 'Pipeline',
        'extras' => 'Notes',
    ],

    'convert' => [
        'action' => 'Convert to customer',
        'modal_heading' => 'Convert lead to customer',
        'modal_description' => 'Creates a Contact with the customer role. Optionally also creates a linked Website.',
        'create_website' => 'Also create a Website',
        'website_name' => 'Website name',
        'website_url' => 'Website URL',
        'success_title' => 'Lead converted',
        'success_body' => 'Contact created and lead archived.',
        'already_converted' => 'This lead has already been converted.',
    ],

    'widgets' => [
        'pipeline' => 'Leads pipeline',
        'no_open_leads' => 'No open leads',
    ],
];
