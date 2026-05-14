<?php

return [
    'singular' => 'Dominio',
    'plural' => 'Domini',

    'sections' => [
        'identity' => 'Dominio',
        'renewal' => 'Rinnovo',
        'links' => 'Collegamenti',
        'links_hint' => 'Lascia vuoto per collegare automaticamente: il sito viene trovato dall\'URL e il cliente dal sito.',
        'extras' => 'Note',
    ],

    'fields' => [
        'name' => 'Nome dominio',
        'registrar' => 'Provider',
        'status' => 'Stato',
        'registered_at' => 'Registrato il',
        'expires_at' => 'Scade il',
        'renewal_period_months' => 'Periodo rinnovo (mesi)',
        'auto_renew' => 'Rinnovo automatico',
        'renewal_cost' => 'Costo rinnovo',
        'owner_contact' => 'Cliente',
        'website' => 'Sito web',
        'notes' => 'Note',
        'days_left' => 'Giorni alla scadenza',
    ],

    'auto_link_placeholder' => 'Automatico',

    'filters' => [
        'expiring_soon' => 'In scadenza (30 giorni)',
        'expired' => 'Scaduti',
    ],

    'widgets' => [
        'expiring' => 'Domini in scadenza — prossimi :days giorni',
        'no_expiring' => 'Nessun dominio in scadenza a breve.',
        'total' => 'Domini totali',
        'active_count' => ':count attivi',
        'expiring_30' => 'In scadenza (30 giorni)',
        'expired_count' => ':count già scaduti',
        'none_expired' => 'Nessuno scaduto',
        'annual_cost' => 'Costo annuo rinnovi',
        'annual_cost_hint' => 'Normalizzato su 12 mesi, domini attivi.',
    ],
];
