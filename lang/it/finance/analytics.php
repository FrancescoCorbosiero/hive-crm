<?php

return [
    'page_title' => 'Analisi finanziaria',
    'fields' => [
        'from' => 'Dal',
        'until' => 'Al',
        'include_non_taxable' => 'Includi entrate esterne',
    ],
    'helpers' => [
        'include_non_taxable' => 'Se attivo, donazioni, contributi e altre voci non imponibili vengono sommate nei totali.',
    ],
    'banners' => [
        'non_taxable_included' => 'Le voci esterne (non imponibili) sono incluse nei valori sotto.',
    ],
    'sections' => [
        'totals' => 'Totali del periodo',
        'income_by_category' => 'Entrate per categoria',
        'loss_by_category' => 'Uscite per categoria',
        'income_by_website' => 'Entrate per sito web',
    ],
    'totals' => [
        'income' => 'Entrate',
        'loss' => 'Uscite',
        'net' => 'Netto',
    ],
    'columns' => [
        'category' => 'Categoria',
        'website' => 'Sito',
        'amount' => 'Importo',
    ],
    'empty' => 'Nessun movimento nel periodo selezionato.',
    'apply' => 'Applica filtro',
];
