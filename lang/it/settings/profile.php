<?php

return [
    'page_title' => 'Anagrafica aziendale',
    'subtitle' => 'Dati che alimentano i template (PDF, XML FatturaPA) e identificano la tua attività.',
    'saved' => 'Anagrafica salvata',
    'save' => 'Salva',

    'tabs' => [
        'anagrafica' => 'Anagrafica',
        'sede' => 'Sede',
        'contatti' => 'Contatti',
        'conti' => 'Conti correnti',
        'note' => 'Note',
    ],

    'types' => [
        'ditta_individuale' => 'Ditta individuale',
        'persona_giuridica' => 'Persona giuridica',
    ],

    'fields' => [
        'type' => 'Tipo',
        'denominazione' => 'Denominazione',
        'nome' => 'Nome',
        'cognome' => 'Cognome',
        'codice_fiscale' => 'Codice fiscale',
        'partita_iva' => 'Partita IVA',
        'regime_fiscale' => 'Regime fiscale',
        'natura_default' => 'Natura IVA predefinita',
        'sede_indirizzo' => 'Indirizzo',
        'sede_civico' => 'Numero civico',
        'sede_cap' => 'CAP',
        'sede_comune' => 'Comune',
        'sede_provincia' => 'Provincia',
        'sede_nazione' => 'Nazione',
        'email' => 'Email',
        'pec_email' => 'PEC',
        'phone' => 'Telefono',
        'website_url' => 'Sito web',
    ],

    'bank' => [
        'add' => 'Aggiungi conto corrente',
        'name' => 'Nome',
        'name_placeholder' => 'es. Conto principale',
        'iban' => 'IBAN',
        'bic' => 'BIC / SWIFT',
        'bank_name' => 'Banca',
        'account_holder' => 'Intestatario',
        'is_primary' => 'Conto principale',
        'is_primary_help' => 'Conto utilizzato di default sulle fatture (DatiPagamento).',
        'notes' => 'Note',
    ],
];
