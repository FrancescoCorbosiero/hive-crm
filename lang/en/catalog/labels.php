<?php

return [
    'singular' => 'Service',
    'plural' => 'Service catalog',

    'sections' => [
        'identity' => 'Service',
        'defaults' => 'Defaults',
        'defaults_hint' => 'Values copied into a quotation or fattura line when the service is picked. The line stays fully editable.',
        'extras' => 'Options',
    ],

    'fields' => [
        'name' => 'Name',
        'category' => 'Category',
        'description' => 'Description',
        'default_unit_price' => 'Default unit price',
        'default_vat_rate' => 'Default VAT rate (%)',
        'default_cadence' => 'Default cadence',
        'default_cadence_none' => 'None',
        'is_active' => 'Active',
        'sort_order' => 'Sort order',
        'notes' => 'Internal notes',
    ],

    'line_picker' => [
        'label' => 'Catalog service',
        'hint' => 'Pick a service to pre-fill the line. Values stay editable.',
    ],
];
