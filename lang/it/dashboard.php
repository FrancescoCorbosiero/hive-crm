<?php

return [
    'title' => 'Cruscotto',
    'subtitle' => 'Panoramica operativa e inserimento rapido dati.',

    'hero' => [
        'greeting' => [
            'morning' => 'Buongiorno',
            'afternoon' => 'Buon pomeriggio',
            'evening' => 'Buonasera',
        ],
        'tagline' => 'Ecco il riepilogo della tua attività. Da qui puoi aggiungere nuovi record, registrare movimenti e monitorare scadenze.',
        'counters' => [
            'open_leads' => 'Opportunità aperte',
            'unpaid_invoices' => 'Fatture da incassare',
            'active_websites' => 'Siti attivi',
            'contacts' => 'Contatti',
        ],
    ],

    'quick_actions' => [
        'heading' => 'Azioni rapide',
        'description' => 'Crea in un clic i record che usi più spesso.',
        'tiles' => [
            'contact' => [
                'label' => 'Nuovo contatto',
                'description' => 'Aggiungi un cliente, fornitore o lead all\'anagrafica.',
            ],
            'website' => [
                'label' => 'Nuovo sito',
                'description' => 'Registra un sito web con scadenze e abbonamento.',
            ],
            'lead' => [
                'label' => 'Nuova opportunità',
                'description' => 'Apri un\'opportunità commerciale nel pipeline vendite.',
            ],
            'quotation' => [
                'label' => 'Nuovo preventivo',
                'description' => 'Componi un preventivo da inviare al cliente.',
            ],
            'fattura' => [
                'label' => 'Nuova fattura',
                'description' => 'Emetti una fattura o ricevuta con linee personalizzate.',
            ],
            'domain' => [
                'label' => 'Nuovo dominio',
                'description' => 'Traccia un dominio e la sua data di scadenza.',
            ],
            'expense' => [
                'label' => 'Nuova spesa',
                'description' => 'Registra una spesa sostenuta nella contabilità.',
            ],
            'service' => [
                'label' => 'Nuovo servizio',
                'description' => 'Aggiungi un servizio al catalogo per i preventivi.',
            ],
        ],
    ],

    'kpis' => [
        'ytd_income' => 'Fatturato (anno)',
        'ytd_income_desc' => 'Incassi da inizio anno',
        'ytd_expense' => 'Spese (anno)',
        'ytd_expense_desc' => 'Uscite da inizio anno',
        'ytd_net' => 'Risultato netto',
        'ytd_net_positive' => 'In utile',
        'ytd_net_negative' => 'In perdita',
        'pipeline' => 'Pipeline aperto',
        'pipeline_desc' => ':count opportunità in lavorazione',
    ],

    'new_record' => [
        'label' => 'Nuovo',
        'contact' => 'Contatto',
        'website' => 'Sito web',
        'quotation' => 'Preventivo',
        'fattura' => 'Fattura',
        'domain' => 'Dominio',
        'lead' => 'Opportunità',
    ],

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
