<?php

return [
    'title' => 'Dashboard',
    'subtitle' => 'Operational overview and fast data entry.',

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
