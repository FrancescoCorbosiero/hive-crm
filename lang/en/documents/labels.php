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
    ],
];
