<?php

declare(strict_types=1);

namespace App\Domains\Websites\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class RenewalApproaching
{
    use Dispatchable;

    public function __construct(
        public readonly int $websiteId,
        public readonly int $daysUntilRenewal,
    ) {}
}
