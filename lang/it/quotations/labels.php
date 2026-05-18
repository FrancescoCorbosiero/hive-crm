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

        'auto_render_pdf' => 'Genera PDF',
        'auto_render_pdf_helper' => 'Genera e archivia il PDF del preventivo appena viene salvato.',
        'mark_as_sent' => 'Segna come inviato',
        'mark_as_sent_helper' => 'Cambia lo stato da Bozza a Inviato. NON invia alcuna email — è solo un\'etichetta di stato.',
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
        'auto_actions' => 'Dopo il salvataggio',
        'auto_actions_hint' => 'Genera il PDF e / o cambia lo stato in Inviato in un colpo solo. Non viene inviata alcuna email — Inviato è solo un\'etichetta di stato.',
    ],

    'actions' => [
        'mark_sent' => 'Segna come inviato',
        'accept' => 'Accetta e crea fattura',
        'reject' => 'Rifiuta',
        'render_pdf' => 'Genera PDF',
        'download_pdf' => 'Scarica PDF',
    ],

    'auto_populate' => [
        'render_failed' => 'Generazione PDF non riuscita — il preventivo è stato comunque salvato.',
        'mark_sent_failed' => 'Impossibile aggiornare lo stato a Inviato — il preventivo è stato comunque salvato.',
    ],

    'notifications' => [
        'accepted_title' => 'Preventivo accettato',
        'accepted_body' => 'Fattura iniziale creata e abbonamenti ricorrenti schedulati per le righe non una-tantum.',
        'cannot_transition' => 'Stato non modificabile: il preventivo è già in stato finale.',
    ],
];
