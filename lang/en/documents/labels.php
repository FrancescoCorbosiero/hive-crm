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
        'record_payment_heading' => 'Record payment for :number',
        'record_payment_success' => 'Payment recorded for :number — revenue entry updated.',
        'record_payment_failure' => 'Could not record payment',
        'record_payments_bulk' => 'Record payments (on issue date)',
        'record_payments_bulk_heading' => 'Record payments for the selected fatture',
        'record_payments_bulk_description' => 'For each fattura that isn\'t already paid or cancelled, records a payment equal to the outstanding amount, dated on its issue date. Already-paid or cancelled fatture are skipped.',
        'record_payments_bulk_submit' => 'Record',
        'record_payments_bulk_success' => ':done payments recorded',
        'record_payments_bulk_summary' => 'Skipped: :skipped · Failed: :failed',
        'pause' => 'Pause',
        'resume' => 'Resume',
        'issue_now' => 'Issue now',
        'backfill' => 'Backfill past months',
        'backfill_heading' => 'Backfill past invoices',
        'backfill_description' => 'Issues one invoice per cycle from the chosen date up to (but not including) the configured next-issue date, each backdated to its cycle. Run this BEFORE issuing any current-period invoices for the same year — numbering is assigned in creation order.',
        'backfill_from' => 'Issue starting from',
        'backfill_submit' => 'Backfill',
        'backfill_success' => '{0} No invoices to backfill.|{1} 1 invoice issued.|[2,*] :count invoices issued.',
        'backfill_failure' => 'Backfill failed',
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
