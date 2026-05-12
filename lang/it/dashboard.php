<?php

return [
    'title' => 'Cruscotto',
    'subtitle' => 'Panoramica operativa e inserimento rapido dati.',

    'fast_entry' => [
        'failure' => 'Operazione non riuscita',

        'record_payment' => [
            'label' => 'Registra pagamento',
            'heading' => 'Registra un pagamento ricevuto',
            'submit' => 'Registra',
            'fattura' => 'Fattura',
            'amount' => 'Importo',
            'paid_at' => 'Data pagamento',
            'reference' => 'Riferimento',
            'success' => 'Pagamento registrato.',
        ],

        'add_lead' => [
            'label' => 'Nuova opportunità',
            'heading' => 'Aggiungi una nuova opportunità',
            'submit' => 'Aggiungi',
            'name' => 'Nome contatto',
            'company' => 'Azienda',
            'email' => 'Email',
            'estimated_value' => 'Valore stimato',
            'source' => 'Origine',
            'status' => 'Stato',
            'success' => 'Opportunità “:name” creata.',
        ],

        'log_expense' => [
            'label' => 'Registra spesa',
            'heading' => 'Registra una spesa',
            'submit' => 'Registra',
            'description' => 'Descrizione',
            'amount' => 'Importo',
            'occurred_at' => 'Data',
            'category' => 'Categoria',
            'vendor' => 'Fornitore',
            'success' => 'Spesa registrata.',
        ],
    ],

    'open_quotations' => [
        'heading' => 'Preventivi aperti',
        'empty' => 'Nessun preventivo aperto.',
        'number' => 'Numero',
        'title' => 'Oggetto',
        'client' => 'Cliente',
        'total' => 'Totale',
        'status' => 'Stato',
        'valid_until' => 'Valido fino al',
    ],

    'top_leads' => [
        'heading' => 'Top 5 opportunità',
        'empty' => 'Nessuna opportunità con valore stimato.',
        'name' => 'Nome',
        'company' => 'Azienda',
        'status' => 'Stato',
        'value' => 'Valore stimato',
        'next_action' => 'Prossima azione',
    ],

    'active_subscriptions' => [
        'heading' => 'Abbonamenti attivi',
        'empty' => 'Nessun abbonamento attivo.',
        'delayed' => 'in ritardo',
        'every_n_months' => 'Ogni :n mesi',
        'kinds' => [
            'website' => 'Sito',
            'recurring_fattura' => 'Fattura ricorrente',
            'recurring_expense' => 'Spesa ricorrente',
        ],
        'cols' => [
            'name' => 'Nome',
            'kind' => 'Tipo',
            'counterparty' => 'Controparte',
            'amount' => 'Importo',
            'frequency' => 'Frequenza',
            'started_at' => 'Iniziato il',
            'next_due_at' => 'Prossima scadenza',
        ],
    ],
];
