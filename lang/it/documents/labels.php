<?php

return [
    'documents' => [
        'singular' => 'Documento',
        'plural' => 'Documenti',
    ],
    'fatture' => [
        'singular' => 'Fattura',
        'plural' => 'Fatture',
    ],

    'fields' => [
        'title' => 'Titolo',
        'category' => 'Categoria',
        'file' => 'File',
        'file_size' => 'Dimensione',
        'mime' => 'Tipo MIME',
        'related' => 'Collegato a',
        'issued_at' => 'Data emissione',

        'number' => 'Numero',
        'year' => 'Anno',
        'fattura_number' => 'N. fattura',
        'client' => 'Cliente',
        'lines' => 'Righe',
        'line_description' => 'Descrizione',
        'line_qty' => 'Quantità',
        'line_unit_price' => 'Prezzo unitario',
        'line_vat_rate' => 'Aliquota IVA %',
        'subtotal' => 'Imponibile',
        'vat' => 'IVA',
        'total' => 'Totale',
        'payment_status' => 'Stato pagamento',
    ],

    'category' => [
        'fattura' => 'Fattura',
        'contract' => 'Contratto',
        'receipt' => 'Ricevuta',
        'other' => 'Altro',
    ],

    'payment_status' => [
        'unpaid' => 'Non pagata',
        'partially_paid' => 'Parzialmente pagata',
        'paid' => 'Pagata',
        'overdue' => 'Scaduta',
        'cancelled' => 'Annullata',
    ],

    'sections' => [
        'header' => 'Intestazione',
        'lines' => 'Righe fattura',
        'totals' => 'Totali',
        'payment' => 'Pagamento',
    ],

    'actions' => [
        'download_pdf' => 'Scarica PDF',
        'render_pdf' => 'Rigenera PDF',
    ],
];
