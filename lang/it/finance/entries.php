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
        'is_taxable' => 'Reddito imponibile',
        'is_taxable_short' => 'Imponibile',
        'source_type' => 'Fonte',
        'source_id' => 'Riferimento',
        'contact' => 'Contatto',
        'notes' => 'Note',
    ],

    'helpers' => [
        'is_taxable' => 'Lascia attivo per denaro guadagnato dall\'attività. Disattivalo per entrate esterne (donazioni, contributi, apporti personali) — non saranno incluse nelle analisi né nelle proiezioni se non esplicitamente richieste.',
    ],

    'tooltips' => [
        'taxable' => 'Reddito imponibile dell\'attività',
        'non_taxable' => 'Entrata esterna — esclusa dalle analisi per default',
    ],

    'filters' => [
        'taxable_any' => 'Tutte le voci',
        'taxable_only' => 'Solo imponibili',
        'non_taxable_only' => 'Solo esterne',
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
        'external' => 'Esterna (donazione / contributo)',
        'other' => 'Altro',
    ],

    'actions' => [
        'generate_fattura' => 'Genera Fattura',
        'generate_fattura_success' => 'Fattura :number creata da questo movimento.',
        'generate_fattura_failure' => 'Impossibile generare la Fattura.',
    ],
];
