<?php

return [
    'page_title' => 'Finance analytics',
    'fields' => [
        'from' => 'From',
        'until' => 'Until',
        'include_non_taxable' => 'Include external income',
    ],
    'helpers' => [
        'include_non_taxable' => 'When on, donations, grants and other non-taxable entries are added to the totals.',
    ],
    'banners' => [
        'non_taxable_included' => 'External (non-taxable) entries are included in the figures below.',
    ],
    'sections' => [
        'totals' => 'Period totals',
        'income_by_category' => 'Income by category',
        'loss_by_category' => 'Loss by category',
        'income_by_website' => 'Income by website',
    ],
    'totals' => [
        'income' => 'Income',
        'loss' => 'Loss',
        'net' => 'Net',
    ],
    'columns' => [
        'category' => 'Category',
        'website' => 'Website',
        'amount' => 'Amount',
    ],
    'empty' => 'No entries in the selected period.',
    'apply' => 'Apply filter',
];
