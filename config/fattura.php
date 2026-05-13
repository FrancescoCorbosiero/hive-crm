<?php

declare(strict_types=1);

/**
 * Fattura / FatturaPA configuration.
 *
 * The Italian electronic invoicing format (FatturaPA) carries the
 * Cedente Prestatore's fiscal identity inside each XML. To recognise
 * "this XML was issued by us" during import — and to populate the
 * Cedente block on export — these values must match the owner's
 * filed identity with the Agenzia delle Entrate.
 *
 * Test these values against an XML you actually submitted via SdI
 * before relying on imports.
 */
return [

    'cedente' => [
        'codice_fiscale' => env('OWNER_CODICE_FISCALE'),
        'partita_iva' => env('OWNER_PARTITA_IVA'),

        // RF01 = ordinary regime, RF19 = forfettario. Used by the
        // exporter (Phase 2). Not consulted during import.
        'regime_fiscale' => env('OWNER_REGIME_FISCALE', 'RF19'),
    ],

];
