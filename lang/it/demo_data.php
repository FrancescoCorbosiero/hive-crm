<?php

return [
    'page_title' => 'Dati di esempio',
    'subtitle' => 'Installa un piccolo set di contatti, siti, opportunità, movimenti e documenti per provare l\'app. Disabilitato di default sulle nuove installazioni.',

    'current_state' => [
        'heading' => 'Stato attuale del workspace',
    ],

    'tables' => [
        'contacts' => 'Contatti',
        'websites' => 'Siti web',
        'financial_entries' => 'Movimenti',
        'leads' => 'Opportunità',
        'documents' => 'Documenti',
    ],

    'help' => [
        'idempotent' => 'L\'installazione è idempotente — eseguirla due volte non duplica le righe. Ogni record di esempio viene fatto upsert su una chiave stabile (email, URL, ecc).',
        'no_uninstall' => 'Non esiste una funzione di disinstallazione. Per ripartire da zero esegui `php artisan migrate:fresh --seed` dal server.',
    ],

    'install' => [
        'action' => 'Installa dati di esempio',
        'confirm' => 'Installa ora',
        'modal_heading' => 'Installare i dati di esempio?',
        'modal_description_empty' => 'Il workspace è vuoto. Verrà popolato con un piccolo set di record di esempio in tutti i domini.',
        'modal_description_non_empty' => 'Il workspace contiene già dei dati. I record di esempio verranno fatti upsert su chiavi stabili: le righe esistenti con la stessa chiave verranno aggiornate, le altre restano invariate.',
        'success_title' => 'Dati di esempio installati',
        'success_body' => 'I record di esempio sono ora disponibili in tutta l\'app.',
        'failure_title' => 'Installazione fallita',
    ],
];
