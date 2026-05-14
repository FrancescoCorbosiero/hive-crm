<?php

return [
    'singular' => 'Movimento',
    'plural' => 'Movimenti',

    'type' => [
        'income' => 'Entrata',
        'loss' => 'Uscita',
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
        'recent_entries' => 'Movimenti recenti',
        'ytd_income' => 'Entrate YTD',
        'ytd_loss' => 'Uscite YTD',
        'ytd_net' => 'Netto YTD',
    ],

    'categories' => [
        'website_subscription' => 'Abbonamento sito',
        'one_time_project' => 'Progetto una-tantum',
        'consulting' => 'Consulenza',
        'hosting' => 'Hosting',
        'domains' => 'Domini',
        'software' => 'Software',
        'tools' => 'Strumenti',
        'travel' => 'Trasferte',
        'taxes' => 'Tasse',
        'other' => 'Altro',
    ],

    'actions' => [
        'generate_fattura' => 'Genera Fattura',
        'generate_fattura_success' => 'Fattura :number creata da questo movimento.',
        'generate_fattura_failure' => 'Impossibile generare la Fattura.',
    ],
];
