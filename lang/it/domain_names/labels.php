<?php

return [
    'singular' => 'Dominio',
    'plural' => 'Domini',

    'sections' => [
        'identity' => 'Dominio',
        'renewal' => 'Rinnovo',
        'links' => 'Collegamenti',
        'links_hint' => 'Lascia vuoto per collegare automaticamente: il sito viene trovato dall\'URL e il cliente dal sito.',
        'register_payment' => 'Registra pagamento',
        'register_payment_hint' => 'Annota il costo di registrazione come uscita nel registro. Etichettato in modo che non possa essere registrato due volte per lo stesso dominio.',
        'create_website' => 'Crea sito web',
        'create_website_hint' => 'Crea il sito web collegato e lo collega automaticamente a questo dominio.',
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
        'register_payment_enabled' => 'Registra il costo nel registro contabile',
        'registration_cost' => 'Costo di registrazione',
        'registration_paid_at' => 'Pagato il',
        'registration_method' => 'Metodo di pagamento',
        'create_website_enabled' => 'Crea il sito web corrispondente',
        'create_website_helper' => 'Saltato automaticamente se esiste già un sito con questo dominio.',
        'new_website_url' => 'URL del sito',
        'new_website_name' => 'Nome del sito',
    ],

    'auto_populate' => [
        'registration_description' => 'Registrazione dominio :name (:registrar)',
        'payment_method_note' => 'Pagato con :method',
    ],

    'auto_link_placeholder' => 'Automatico',

    'filters' => [
        'expiring_soon' => 'In scadenza (30 giorni)',
        'expired' => 'Scaduti',
    ],

    'actions' => [
        'log_renewal' => 'Registra rinnovo',
        'log_renewal_hint' => 'Crea una voce di spesa per il rinnovo e sposta la data di scadenza al periodo successivo.',
        'log_renewal_description' => 'Rinnovo dominio :name (:registrar)',
        'log_renewal_success' => 'Rinnovo registrato e scadenza aggiornata.',
        'log_renewal_already' => 'Rinnovo già registrato per questo ciclo.',
        'log_renewal_no_cost' => 'Imposta prima il costo di rinnovo del dominio.',
    ],

    'widgets' => [
        'expiring' => 'Domini in scadenza o scaduti — finestra :days giorni',
        'no_expiring' => 'Nessun dominio in scadenza a breve.',
        'expired_badge' => 'scaduto da :days g',
        'total' => 'Domini totali',
        'active_count' => ':count attivi',
        'expiring_30' => 'In scadenza (30 giorni)',
        'expired_count' => ':count già scaduti',
        'none_expired' => 'Nessuno scaduto',
        'annual_cost' => 'Costo annuo rinnovi',
        'annual_cost_hint' => 'Normalizzato su 12 mesi, domini attivi.',
    ],
];
