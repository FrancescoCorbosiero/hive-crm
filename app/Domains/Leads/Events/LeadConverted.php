<?php

declare(strict_types=1);

namespace App\Domains\Leads\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched at the end of LeadsService::convert() after the Contact
 * (and optionally Website and / or Quotation) have been materialised.
 *
 * Currently has no registered listener — fire-and-forget, kept as
 * a stable extension point so future cross-domain hooks (e.g. Mail
 * → send welcome email, or a relationship-graph indexer) can
 * subscribe without modifying LeadsService.
 */
final class LeadConverted
{
    use Dispatchable;

    public function __construct(
        public readonly int $leadId,
        public readonly int $contactId,
        public readonly ?int $websiteId = null,
        public readonly ?int $quotationId = null,
    ) {}
}
