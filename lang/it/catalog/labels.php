<?php

return [
    'singular' => 'Servizio',
    'plural' => 'Catalogo servizi',

    'sections' => [
        'identity' => 'Servizio',
        'defaults' => 'Valori predefiniti',
        'defaults_hint' => 'Valori copiati nella riga di un preventivo o di una fattura quando il servizio viene scelto. La riga resta sempre modificabile.',
        'extras' => 'Opzioni',
    ],

    'fields' => [
        'name' => 'Nome',
        'category' => 'Categoria',
        'description' => 'Descrizione',
        'default_unit_price' => 'Prezzo unitario predefinito',
        'default_vat_rate' => 'IVA predefinita (%)',
        'default_cadence' => 'Cadenza predefinita',
        'default_cadence_none' => 'Nessuna',
        'is_active' => 'Attivo',
        'sort_order' => 'Ordinamento',
        'notes' => 'Note interne',
    ],

    'line_picker' => [
        'label' => 'Servizio da catalogo',
        'hint' => 'Scegli un servizio per precompilare la riga. I valori restano modificabili.',
    ],
];
