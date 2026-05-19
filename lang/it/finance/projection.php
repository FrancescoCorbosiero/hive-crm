<?php

return [
    'page_title' => 'Proiezione cash flow',
    'recompute' => 'Ricalcola',
    'derived_hint' => 'Derivato dalle fatture ricorrenti attive e dai rinnovi dei domini. Nessun record viene creato.',
    'empty' => 'Nessuna proiezione nella finestra selezionata. Aggiungi una fattura ricorrente o imposta il costo di rinnovo su un dominio per vedere le voci qui.',

    'fields' => [
        'window' => 'Finestra di proiezione',
    ],

    'windows' => [
        3 => '3 mesi',
        6 => '6 mesi',
        12 => '12 mesi',
        24 => '24 mesi',
    ],

    'sections' => [
        'totals' => 'Totali proiettati — prossimi :n mesi',
        'monthly' => 'Mese per mese',
        'entries' => 'Voci proiettate',
    ],

    'totals' => [
        'income' => 'Ricavi proiettati',
        'loss' => 'Uscite proiettate',
        'net' => 'Netto proiettato',
    ],

    'columns' => [
        'date' => 'Data',
        'description' => 'Descrizione',
        'source' => 'Origine',
        'amount' => 'Importo',
    ],

    'sources' => [
        'recurring_fattura' => 'Fattura ricorrente',
        'domain' => 'Rinnovo dominio',
        'website' => 'Rinnovo sito',
    ],

    'entries' => [
        'domain_renewal' => 'Rinnovo — :name',
        'website_renewal' => 'Hosting — :name',
    ],
];
