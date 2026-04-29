<?php

return [
    'singular' => 'Opportunità',
    'plural' => 'Opportunità',

    'fields' => [
        'name' => 'Nome',
        'email' => 'Email',
        'phone' => 'Telefono',
        'source' => 'Provenienza',
        'status' => 'Stato',
        'estimated_value' => 'Valore stimato',
        'next_action_at' => 'Prossima azione',
        'notes' => 'Note',
        'converted_contact' => 'Contatto creato',
        'converted_at' => 'Convertito il',
    ],

    'sections' => [
        'identity' => 'Anagrafica',
        'pipeline' => 'Pipeline',
        'extras' => 'Note',
    ],

    'convert' => [
        'action' => 'Converti in cliente',
        'modal_heading' => 'Converti opportunità in cliente',
        'modal_description' => 'Crea un Contatto con ruolo cliente. Opzionalmente, crea anche un Sito web associato.',
        'create_website' => 'Crea anche un Sito web',
        'website_name' => 'Nome del sito',
        'website_url' => 'URL del sito',
        'success_title' => 'Opportunità convertita',
        'success_body' => 'Contatto creato e opportunità archiviata.',
        'already_converted' => 'Questa opportunità è già stata convertita.',
    ],

    'widgets' => [
        'pipeline' => 'Pipeline opportunità',
        'no_open_leads' => 'Nessuna opportunità aperta',
    ],
];
