<?php

return [
    'singular' => 'Campaign',
    'plural' => 'Campaigns',

    'status' => [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'sending' => 'Sending',
        'sent' => 'Sent',
        'cancelled' => 'Cancelled',
    ],

    'fields' => [
        'name' => 'Name',
        'subject' => 'Subject',
        'body_html' => 'HTML body',
        'status' => 'Status',
        'scheduled_at' => 'Scheduled at',
        'sent_at' => 'Sent at',
        'sent_count' => 'Sent',
        'delivered_count' => 'Delivered',
        'bounced_count' => 'Bounced',
        'complained_count' => 'Complaints',
        'opened_count' => 'Opens',
        'clicked_count' => 'Clicks',
        'unsubscribed_count' => 'Unsubscribed',
    ],

    'sections' => [
        'content' => 'Content',
        'schedule' => 'Schedule',
        'stats' => 'Stats',
    ],

    'actions' => [
        'send_now' => 'Send now',
        'schedule' => 'Schedule',
        'cancel' => 'Cancel',
    ],

    'notifications' => [
        'dispatched_title' => 'Campaign queued',
        'dispatched_body' => 'Messages have been queued and will be sent shortly.',
        'cannot_send_final' => 'This campaign has already been sent or cancelled.',
    ],

    'widgets' => [
        'in_flight' => 'Campaign in flight',
    ],
];
