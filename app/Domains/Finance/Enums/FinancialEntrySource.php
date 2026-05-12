<?php

declare(strict_types=1);

namespace App\Domains\Finance\Enums;

/**
 * Stable string aliases for the FinancialEntry polymorphic source
 * column. These values are written to the DB. Adding a new source ⇒
 * add a new case here; never reuse an existing value for a different
 * meaning.
 *
 * Finance never imports the underlying domain models — it only stores
 * the alias + integer source_id pair. Domain-aware lookups happen
 * through each owning domain's public service.
 */
enum FinancialEntrySource: string
{
    case Website = 'website';
    case Lead = 'lead';
    case Fattura = 'fattura';
    case Contact = 'contact';
}
