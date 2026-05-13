<?php

return [
    'singular' => 'Preventivo',
    'plural' => 'Preventivi',

    'status' => [
        'draft' => 'Bozza',
        'sent' => 'Inviato',
        'accepted' => 'Accettato',
        'rejected' => 'Rifiutato',
        'expired' => 'Scaduto',
    ],

    'fields' => [
        'number' => 'Numero',
        'year' => 'Anno',
        'preventivo_number' => 'N. preventivo',
        'name' => 'Titolo',
        'client' => 'Cliente',
        'lead' => 'Opportunità',
        'issued_at' => 'Data emissione',
        'valid_until' => 'Valido fino a',
        'status' => 'Stato',
        'subtotal' => 'Imponibile',
        'vat' => 'IVA',
        'total' => 'Totale',

        'lines' => 'Righe',
        'line_description' => 'Descrizione',
        'line_qty' => 'Quantità',
        'line_unit_price' => 'Prezzo unitario',
        'line_vat_rate' => 'Aliquota IVA %',
        'line_cadence' => 'Cadenza',
    ],

    'cadence' => [
        'una_tantum' => 'Una tantum',
        'monthly' => 'Mensile',
        'quarterly' => 'Trimestrale',
        'yearly' => 'Annuale',
    ],

    'sections' => [
        'header' => 'Intestazione',
        'lines' => 'Righe preventivo',
        'extras' => 'Note',
    ],

    'actions' => [
        'mark_sent' => 'Segna come inviato',
        'accept' => 'Accetta e crea fattura',
        'reject' => 'Rifiuta',
        'render_pdf' => 'Genera PDF',
        'download_pdf' => 'Scarica PDF',
    ],

    'notifications' => [
        'accepted_title' => 'Preventivo accettato',
        'accepted_body' => 'Fattura iniziale creata e abbonamenti ricorrenti schedulati per le righe non una-tantum.',
        'cannot_transition' => 'Stato non modificabile: il preventivo è già in stato finale.',
    ],
];
