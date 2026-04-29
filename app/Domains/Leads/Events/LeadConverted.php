<?php

declare(strict_types=1);

namespace App\Domains\Leads\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class LeadConverted
{
    use Dispatchable;

    public function __construct(
        public readonly int $leadId,
        public readonly int $contactId,
        public readonly ?int $websiteId = null,
    ) {}
}
