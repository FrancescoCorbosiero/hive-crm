<?php

return [
    'page_title' => 'Business profile',
    'subtitle' => 'Data that feeds PDF / XML templates and identifies your business.',
    'saved' => 'Profile saved',
    'save' => 'Save',

    'tabs' => [
        'anagrafica' => 'Registry',
        'sede' => 'Address',
        'contatti' => 'Contacts',
        'conti' => 'Bank accounts',
        'note' => 'Notes',
    ],

    'types' => [
        'ditta_individuale' => 'Sole proprietor',
        'persona_giuridica' => 'Legal entity',
    ],

    'fields' => [
        'type' => 'Type',
        'denominazione' => 'Denomination',
        'nome' => 'First name',
        'cognome' => 'Last name',
        'codice_fiscale' => 'Codice Fiscale',
        'partita_iva' => 'VAT number (P.IVA)',
        'regime_fiscale' => 'Fiscal regime',
        'natura_default' => 'Default VAT exemption (Natura)',
        'sede_indirizzo' => 'Street',
        'sede_civico' => 'Number',
        'sede_cap' => 'ZIP',
        'sede_comune' => 'City',
        'sede_provincia' => 'Province',
        'sede_nazione' => 'Country',
        'email' => 'Email',
        'pec_email' => 'PEC',
        'phone' => 'Phone',
        'website_url' => 'Website',
    ],

    'bank' => [
        'add' => 'Add bank account',
        'name' => 'Name',
        'name_placeholder' => 'e.g. Main account',
        'iban' => 'IBAN',
        'bic' => 'BIC / SWIFT',
        'bank_name' => 'Bank',
        'account_holder' => 'Account holder',
        'is_primary' => 'Primary account',
        'is_primary_help' => 'Used by default on invoices (DatiPagamento block).',
        'notes' => 'Notes',
    ],
];
