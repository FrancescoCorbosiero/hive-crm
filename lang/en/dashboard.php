<?php

return [
    'title' => 'Dashboard',
    'subtitle' => 'Operational overview and fast data entry.',

    'hero' => [
        'greeting' => [
            'morning' => 'Good morning',
            'afternoon' => 'Good afternoon',
            'evening' => 'Good evening',
        ],
        'tagline' => 'Here\'s a snapshot of your business. From here you can add new records, log transactions and keep an eye on upcoming deadlines.',
        'counters' => [
            'open_leads' => 'Open leads',
            'unpaid_invoices' => 'Unpaid invoices',
            'active_websites' => 'Active websites',
            'contacts' => 'Contacts',
        ],
    ],

    'quick_actions' => [
        'heading' => 'Quick actions',
        'description' => 'One-click creation of the records you use most.',
        'tiles' => [
            'contact' => [
                'label' => 'New contact',
                'description' => 'Add a customer, vendor or lead to your address book.',
            ],
            'website' => [
                'label' => 'New website',
                'description' => 'Track a client website with renewal and subscription details.',
            ],
            'lead' => [
                'label' => 'New lead',
                'description' => 'Open a sales opportunity in your pipeline.',
            ],
            'quotation' => [
                'label' => 'New quotation',
                'description' => 'Compose a quote to send to a client.',
            ],
            'fattura' => [
                'label' => 'New invoice',
                'description' => 'Issue an invoice or receipt with custom line items.',
            ],
            'domain' => [
                'label' => 'New domain',
                'description' => 'Track a domain name and its expiry date.',
            ],
            'expense' => [
                'label' => 'New expense',
                'description' => 'Log a business expense into your books.',
            ],
            'service' => [
                'label' => 'New service',
                'description' => 'Add a service to the catalog for use in quotes.',
            ],
        ],
    ],

    'kpis' => [
        'ytd_income' => 'Revenue (YTD)',
        'ytd_income_desc' => 'Income since Jan 1',
        'ytd_expense' => 'Expenses (YTD)',
        'ytd_expense_desc' => 'Outgoings since Jan 1',
        'ytd_net' => 'Net result',
        'ytd_net_positive' => 'In profit',
        'ytd_net_negative' => 'In the red',
        'pipeline' => 'Open pipeline',
        'pipeline_desc' => ':count leads in progress',
    ],

    'new_record' => [
        'label' => 'New',
        'contact' => 'Contact',
        'website' => 'Website',
        'quotation' => 'Quotation',
        'fattura' => 'Invoice',
        'domain' => 'Domain',
        'lead' => 'Lead',
    ],

    'fast_entry' => [
        'failure' => 'Operation failed',

        'record_payment' => [
            'label' => 'Record payment',
            'heading' => 'Record a received payment',
            'submit' => 'Record',
            'fattura' => 'Invoice',
            'amount' => 'Amount',
            'paid_at' => 'Paid on',
            'reference' => 'Reference',
            'success' => 'Payment recorded.',
        ],

        'add_lead' => [
            'label' => 'New lead',
            'heading' => 'Add a new lead',
            'submit' => 'Add',
            'name' => 'Contact name',
            'company' => 'Company',
            'email' => 'Email',
            'estimated_value' => 'Estimated value',
            'source' => 'Source',
            'status' => 'Status',
            'success' => 'Lead “:name” created.',
        ],

        'log_expense' => [
            'label' => 'Log expense',
            'heading' => 'Log an expense',
            'submit' => 'Log',
            'description' => 'Description',
            'amount' => 'Amount',
            'occurred_at' => 'Date',
            'category' => 'Category',
            'vendor' => 'Vendor',
            'success' => 'Expense logged.',
        ],
    ],

    'open_quotations' => [
        'heading' => 'Open quotations',
        'empty' => 'No open quotations.',
        'number' => 'Number',
        'title' => 'Subject',
        'client' => 'Client',
        'total' => 'Total',
        'status' => 'Status',
        'valid_until' => 'Valid until',
    ],

    'top_leads' => [
        'heading' => 'Top 5 leads',
        'empty' => 'No leads with an estimated value.',
        'name' => 'Name',
        'company' => 'Company',
        'status' => 'Status',
        'value' => 'Estimated value',
        'next_action' => 'Next action',
    ],

    'active_subscriptions' => [
        'heading' => 'Active subscriptions',
        'empty' => 'No active subscriptions.',
        'delayed' => 'delayed',
        'every_n_months' => 'Every :n months',
        'kinds' => [
            'website' => 'Website',
            'recurring_fattura' => 'Recurring invoice',
            'recurring_expense' => 'Recurring expense',
        ],
        'cols' => [
            'name' => 'Name',
            'kind' => 'Type',
            'counterparty' => 'Counterparty',
            'amount' => 'Amount',
            'frequency' => 'Frequency',
            'started_at' => 'Started',
            'next_due_at' => 'Next due',
        ],
    ],
];
