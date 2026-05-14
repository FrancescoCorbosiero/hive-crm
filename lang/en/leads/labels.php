<?php

return [
    'singular' => 'Lead',
    'plural' => 'Leads',

    'fields' => [
        'name' => 'Name',
        'company_name' => 'Company',
        'social_url' => 'Social URL',
        'email' => 'Email',
        'phone' => 'Phone',
        'source' => 'Source',
        'status' => 'Status',
        'estimated_value' => 'Estimated value',
        'next_action_at' => 'Next action',
        'last_contacted_at' => 'Last contact',
        'lost_reason' => 'Lost reason',
        'notes' => 'Notes',
        'converted_contact' => 'Created contact',
        'converted_at' => 'Converted at',
        'business_category' => 'Industry',
        'website_type' => 'Website type',
        'budget_tier' => 'Budget tier',
        'is_redesign' => 'Redesign',
        'is_estero' => 'Foreign client',
    ],

    'helpers' => [
        'company_name' => 'Auto-filled from the email domain when left blank.',
        'is_redesign' => 'They already have a website and want it redone.',
        'is_estero' => 'Client is based outside Italy.',
    ],

    'sections' => [
        'identity' => 'Identity',
        'qualification' => 'Qualification',
        'pipeline' => 'Pipeline',
        'extras' => 'Notes',
    ],

    'filters' => [
        'stale' => 'Stale (no contact in 14 days)',
    ],

    'never_contacted' => 'Never contacted',

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

    'invoice' => [
        'action' => 'Issue invoice',
        'success' => 'Draft fattura created.',
        'failed' => 'Could not create fattura.',
    ],

    'widgets' => [
        'pipeline' => 'Leads pipeline',
        'pipeline_value' => 'Pipeline value by stage',
        'lead_count' => '{0} no leads|{1} :count lead|[2,*] :count leads',
        'stale_leads' => 'Stale leads — no contact in :days days',
        'no_open_leads' => 'No open leads',
        'no_stale_leads' => 'No stale leads — every open lead has been touched recently.',
    ],
];
