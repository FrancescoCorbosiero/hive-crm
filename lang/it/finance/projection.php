<?php

return [
    'page_title' => 'Proiezione cash flow',
    'recompute' => 'Ricalcola',
    'derived_hint' => 'Derivato da fatture ricorrenti, fatture da incassare, spese ricorrenti e rinnovi di domini / hosting. Le voci scadute contano nel mese corrente. Nessun record viene creato.',
    'empty' => 'Nessuna proiezione nella finestra selezionata. Aggiungi una fattura o una spesa ricorrente, o imposta il costo di rinnovo su un dominio o un sito, per vedere le voci qui.',
    'overdue' => 'scaduto',

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
        'invoice' => 'Fattura da incassare',
        'recurring_expense' => 'Spesa ricorrente',
        'domain' => 'Rinnovo dominio',
        'website' => 'Rinnovo sito',
    ],
];
