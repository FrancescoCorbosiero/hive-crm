<?php

return [
    'singular' => 'Movimento',
    'plural' => 'Movimenti',

    'type' => [
        'income' => 'Entrata',
        'expense' => 'Uscita',
    ],

    'fields' => [
        'type' => 'Tipo',
        'amount' => 'Importo',
        'occurred_at' => 'Data',
        'description' => 'Descrizione',
        'category' => 'Categoria',
        'source_type' => 'Fonte',
        'source_id' => 'Riferimento',
        'contact' => 'Contatto',
        'notes' => 'Note',
    ],

    'sections' => [
        'overview' => 'Generale',
        'attribution' => 'Attribuzione',
        'extras' => 'Note',
    ],

    'widgets' => [
        'monthly_income' => 'Entrate mensili (ultimi 12 mesi)',
        'recent_transactions' => 'Movimenti recenti',
        'ytd_income' => 'Entrate YTD',
        'ytd_expense' => 'Uscite YTD',
        'ytd_net' => 'Netto YTD',
    ],

    'categories' => [
        'website_subscription' => 'Abbonamento sito',
        'one_time_project' => 'Progetto una-tantum',
        'consulting' => 'Consulenza',
        'hosting' => 'Hosting',
        'software' => 'Software',
        'tools' => 'Strumenti',
        'travel' => 'Trasferte',
        'taxes' => 'Tasse',
        'other' => 'Altro',
    ],
];
