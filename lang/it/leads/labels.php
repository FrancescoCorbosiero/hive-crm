<?php

return [
    'singular' => 'Opportunità',
    'plural' => 'Opportunità',

    'fields' => [
        'name' => 'Nome',
        'company_name' => 'Azienda',
        'social_url' => 'URL social',
        'email' => 'Email',
        'phone' => 'Telefono',
        'source' => 'Provenienza',
        'status' => 'Stato',
        'estimated_value' => 'Valore stimato',
        'next_action_at' => 'Prossima azione',
        'last_contacted_at' => 'Ultimo contatto',
        'lost_reason' => 'Motivo della perdita',
        'notes' => 'Note',
        'converted_contact' => 'Contatto creato',
        'converted_at' => 'Convertito il',
        'business_category' => 'Settore',
        'website_type' => 'Tipo sito',
        'budget_tier' => 'Fascia budget',
        'is_redesign' => 'Redesign',
        'is_estero' => 'Estero',
    ],

    'helpers' => [
        'company_name' => 'Compilato automaticamente dal dominio email se lasciato vuoto.',
        'is_redesign' => 'Il cliente ha già un sito e vuole rifarlo.',
        'is_estero' => 'Cliente con sede fuori dall\'Italia.',
    ],

    'sections' => [
        'identity' => 'Anagrafica',
        'qualification' => 'Qualifica',
        'pipeline' => 'Pipeline',
        'extras' => 'Note',
    ],

    'filters' => [
        'stale' => 'Inattive (nessun contatto da 14 giorni)',
    ],

    'never_contacted' => 'Mai contattato',

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

    'invoice' => [
        'action' => 'Emetti fattura',
        'success' => 'Fattura bozza creata.',
        'failed' => 'Impossibile creare la fattura.',
    ],

    'widgets' => [
        'pipeline' => 'Pipeline opportunità',
        'pipeline_value' => 'Valore pipeline per fase',
        'lead_count' => '{0} nessuna|{1} :count opportunità|[2,*] :count opportunità',
        'stale_leads' => 'Opportunità inattive — nessun contatto da :days giorni',
        'no_open_leads' => 'Nessuna opportunità aperta',
        'no_stale_leads' => 'Nessuna opportunità inattiva — tutte aggiornate di recente.',
    ],
];
