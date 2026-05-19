<?php

return [
    'singular' => 'Sito web',
    'plural' => 'Siti web',

    'name' => 'Nome',
    'url' => 'URL',
    'status' => 'Stato',
    'owner_contact' => 'Cliente',
    'tech_stack' => 'Tecnologie',
    'notes' => 'Note',
    'subscription_started_at' => 'Inizio abbonamento',
    'next_renewal_at' => 'Prossimo rinnovo',
    'renewal_period_months' => 'Periodo di rinnovo (mesi)',
    'renewal_cost' => 'Costo di rinnovo (per ciclo)',
    'renewal_cost_helper' => 'Costo ricorrente per ciclo. Alimenta la pagina Proiezione Cash Flow. Lascia vuoto se l\'hosting è incluso in altro.',
    'days_until_renewal' => 'Giorni al rinnovo',
    'trello_board_url' => 'URL board Trello',
    'trello_board_url_short' => 'Trello',
    'trello_open' => 'Apri Trello',

    'section' => [
        'general' => 'Informazioni generali',
        'subscription' => 'Abbonamento',
        'tech' => 'Tecnologie',
        'register_cost' => 'Registra costo iniziale',
        'register_cost_hint' => 'Annota il costo di setup o del primo ciclo di hosting come uscita. Etichettato in modo che non possa essere registrato due volte per lo stesso sito.',
        'register_domain' => 'Registra il dominio corrispondente',
        'register_domain_hint' => 'Crea anche un record Dominio collegato a questo Sito. Saltato automaticamente se esiste già un dominio con questo host.',
    ],

    'cost' => [
        'toggle' => 'Registra il costo nel registro contabile',
        'amount' => 'Importo',
        'paid_at' => 'Pagato il',
        'method' => 'Metodo di pagamento',
    ],

    'domain' => [
        'toggle' => 'Registra anche il dominio corrispondente',
        'toggle_helper' => 'Deriva l\'host dall\'URL del sito sopra.',
        'registrar' => 'Provider',
        'registered_at' => 'Registrato il',
        'renewal_period_months' => 'Periodo di rinnovo (mesi)',
        'cost_amount' => 'Costo registrazione dominio',
        'cost_helper' => 'Lascia vuoto per non registrare il costo. Altrimenti crea una voce di uscita in Finance, etichettata in modo che non venga registrata due volte.',
        'cost_paid_at' => 'Dominio pagato il',
        'cost_method' => 'Metodo di pagamento dominio',
    ],

    'auto_populate' => [
        'setup_description' => 'Setup / hosting sito — :name',
        'payment_method_note' => 'Pagato con :method',
    ],

    'widgets' => [
        'upcoming_renewals' => 'Prossimi rinnovi',
        'no_upcoming_renewals' => 'Nessun rinnovo nei prossimi 30 giorni',
        'down_websites' => 'Siti offline',
        'no_down_websites' => 'Tutti i siti attivi rispondono.',
    ],

    'ping' => [
        'is_up' => 'Online',
        'last_status_code' => 'Ultimo HTTP',
        'last_pinged_at' => 'Ultimo controllo',
        'never' => 'Mai controllato',
    ],
];
