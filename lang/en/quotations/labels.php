<?php

return [
    'singular' => 'Quotation',
    'plural' => 'Quotations',

    'status' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
    ],

    'fields' => [
        'number' => 'Number',
        'year' => 'Year',
        'preventivo_number' => 'Quote #',
        'name' => 'Title',
        'client' => 'Client',
        'lead' => 'Lead',
        'issued_at' => 'Issued at',
        'valid_until' => 'Valid until',
        'status' => 'Status',
        'subtotal' => 'Subtotal',
        'vat' => 'VAT',
        'total' => 'Total',

        'lines' => 'Lines',
        'line_description' => 'Description',
        'line_qty' => 'Qty',
        'line_unit_price' => 'Unit price',
        'line_vat_rate' => 'VAT rate %',
    ],

    'sections' => [
        'header' => 'Header',
        'lines' => 'Quotation lines',
        'extras' => 'Notes',
    ],

    'actions' => [
        'mark_sent' => 'Mark as sent',
        'accept' => 'Accept & draft invoice',
        'reject' => 'Reject',
        'render_pdf' => 'Render PDF',
        'download_pdf' => 'Download PDF',
    ],

    'notifications' => [
        'accepted_title' => 'Quotation accepted',
        'accepted_body' => 'Draft invoice created in unpaid state.',
        'cannot_transition' => 'Cannot transition: the quotation is already in a final state.',
    ],
];
