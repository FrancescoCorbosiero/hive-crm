<?php

return [
    'singular' => 'Campagna',
    'plural' => 'Campagne',

    'status' => [
        'draft' => 'Bozza',
        'scheduled' => 'Programmata',
        'sending' => 'In invio',
        'sent' => 'Inviata',
        'cancelled' => 'Annullata',
    ],

    'fields' => [
        'name' => 'Nome',
        'subject' => 'Oggetto',
        'body_html' => 'Corpo HTML',
        'status' => 'Stato',
        'scheduled_at' => 'Programmata per',
        'sent_at' => 'Inviata il',
        'sent_count' => 'Inviate',
        'delivered_count' => 'Consegnate',
        'bounced_count' => 'Bounce',
        'complained_count' => 'Complaint',
        'opened_count' => 'Aperture',
        'clicked_count' => 'Click',
        'unsubscribed_count' => 'Disiscritti',
    ],

    'sections' => [
        'content' => 'Contenuto',
        'schedule' => 'Pianificazione',
        'stats' => 'Statistiche',
    ],

    'actions' => [
        'send_now' => 'Invia ora',
        'schedule' => 'Pianifica',
        'cancel' => 'Annulla',
    ],

    'notifications' => [
        'dispatched_title' => 'Campagna in invio',
        'dispatched_body' => 'I messaggi sono stati accodati e verranno spediti a breve.',
        'cannot_send_final' => 'Questa campagna è già stata inviata o annullata.',
    ],

    'widgets' => [
        'in_flight' => 'Campagna in corso',
    ],
];
