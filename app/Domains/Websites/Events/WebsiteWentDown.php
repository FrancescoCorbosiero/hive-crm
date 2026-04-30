<?php

declare(strict_types=1);

namespace App\Domains\Websites\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a website transitions from is_up=true to is_up=false.
 *
 * Only fires on the transition, never on a steady-state down site —
 * keeps notification listeners idempotent and avoids spam loops.
 */
final class WebsiteWentDown
{
    use Dispatchable;

    public function __construct(
        public readonly int $websiteId,
        public readonly ?int $statusCode,
    ) {}
}
