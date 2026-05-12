<?php

return [
    'singular' => 'Operazione pianificata',
    'plural' => 'Operazioni pianificate',

    'name' => 'Nome',
    'command' => 'Comando artisan',
    'command_help' => 'Seleziona da una whitelist di comandi sicuri. I comandi di sistema non possono essere modificati.',
    'cron_expression' => 'Espressione cron',
    'cron_help' => 'Sintassi cron a 5 campi (minuto ora giorno mese giornoSettimana). Esempi: "0 6 * * *" ogni giorno alle 06:00, "*/15 * * * *" ogni 15 min.',
    'cron_invalid' => 'Espressione cron non valida.',
    'timezone' => 'Fuso orario',
    'description' => 'Descrizione',
    'is_enabled' => 'Abilitata',
    'is_enabled_short' => 'Attiva',
    'is_system' => 'Operazione di sistema',
    'is_system_short' => 'Sistema',
    'without_overlapping' => 'No sovrapposizioni',
    'without_overlapping_help' => 'Salta una esecuzione se la precedente è ancora in corso.',
    'on_one_server' => 'Solo un server',
    'on_one_server_help' => 'In ambienti multi-server, esegui solo su un nodo.',

    'next_run' => 'Prossima esecuzione',
    'last_run' => 'Ultima esecuzione',
    'last_started_at' => 'Iniziata alle',
    'last_finished_at' => 'Terminata alle',
    'last_exit_code' => 'Exit code',
    'last_status' => 'Stato',
    'last_output' => 'Output',
    'duration' => 'Durata',

    'status' => [
        'never' => 'Mai eseguita',
        'running' => 'In esecuzione',
        'success' => 'Successo',
        'failure' => 'Fallita',
    ],

    'run_now' => 'Esegui ora',
    'run_now_done' => 'Operazione terminata (exit :code).',
    'run_now_failed' => 'Esecuzione fallita',
    'run_now_unknown' => 'Comando non nella whitelist — esecuzione rifiutata.',
    'sync_system' => 'Sincronizza operazioni di sistema',
    'sync_done' => 'Operazioni di sistema sincronizzate.',

    'section' => [
        'task' => 'Operazione',
        'execution' => 'Opzioni di esecuzione',
        'last_run' => 'Ultima esecuzione',
    ],
];
