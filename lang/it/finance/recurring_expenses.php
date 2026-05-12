<?php

return [
    'singular' => 'Spesa ricorrente',
    'plural' => 'Spese ricorrenti',

    'sections' => [
        'overview' => 'Dettagli',
        'schedule' => 'Cadenza',
        'extras' => 'Note',
    ],

    'fields' => [
        'name' => 'Nome',
        'frequency' => 'Frequenza',
        'amount' => 'Importo',
        'category' => 'Categoria',
        'vendor' => 'Fornitore',
        'started_at' => 'Iniziata il',
        'next_due_at' => 'Prossima scadenza',
        'last_logged_at' => 'Ultima registrazione',
        'is_active' => 'Attiva',
        'notes' => 'Note',
    ],

    'actions' => [
        'log_occurrence' => 'Registra rata',
        'log_occurrence_hint' => 'Crea una voce di spesa per la prossima scadenza e avanza la cadenza al periodo successivo.',
        'log_occurrence_success' => 'Rata registrata e prossima scadenza avanzata.',
    ],
];
