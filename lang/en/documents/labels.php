<?php

return [
    'documents' => [
        'singular' => 'Document',
        'plural' => 'Documents',
    ],
    'fatture' => [
        'singular' => 'Invoice',
        'plural' => 'Invoices',
    ],

    'fields' => [
        'title' => 'Title',
        'category' => 'Category',
        'file' => 'File',
        'file_size' => 'Size',
        'mime' => 'MIME type',
        'related' => 'Related to',
        'issued_at' => 'Issued at',

        'number' => 'Number',
        'year' => 'Year',
        'fattura_number' => 'Invoice #',
        'client' => 'Client',
        'lines' => 'Lines',
        'line_description' => 'Description',
        'line_qty' => 'Qty',
        'line_unit_price' => 'Unit price',
        'line_vat_rate' => 'VAT rate %',
        'subtotal' => 'Subtotal',
        'vat' => 'VAT',
        'total' => 'Total',
        'payment_status' => 'Payment status',
    ],

    'category' => [
        'fattura' => 'Invoice',
        'contract' => 'Contract',
        'receipt' => 'Receipt',
        'other' => 'Other',
    ],

    'payment_status' => [
        'unpaid' => 'Unpaid',
        'partially_paid' => 'Partially paid',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
    ],

    'sections' => [
        'header' => 'Header',
        'lines' => 'Invoice lines',
        'totals' => 'Totals',
        'payment' => 'Payment',
    ],

    'actions' => [
        'download_pdf' => 'Download PDF',
        'render_pdf' => 'Re-render PDF',
        'record_payment' => 'Record payment',
        'pause' => 'Pause',
        'resume' => 'Resume',
        'issue_now' => 'Issue now',
    ],

    'payment' => [
        'singular' => 'Payment',
        'plural' => 'Payments',
        'paid_at' => 'Paid at',
        'amount' => 'Amount',
        'method' => 'Method',
        'reference' => 'Reference',
        'notes' => 'Notes',
        'outstanding' => 'Outstanding',
        'fully_paid' => 'Fully paid',
        'due_date' => 'Due date',
        'days_overdue' => 'Days overdue',
    ],

    'payment_method' => [
        'bank_transfer' => 'Bank transfer',
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'cash' => 'Cash',
        'check' => 'Check',
        'other' => 'Other',
    ],

    'recurring' => [
        'singular' => 'Recurring invoice',
        'plural' => 'Recurring invoices',
        'name' => 'Name',
        'frequency' => 'Frequency',
        'day_of_month' => 'Day of month',
        'next_issue_at' => 'Next issue',
        'last_issued_at' => 'Last issued',
        'is_active' => 'Active',
    ],

    'frequency' => [
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
    ],
];
