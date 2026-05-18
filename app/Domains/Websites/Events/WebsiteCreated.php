<?php

declare(strict_types=1);

namespace App\Domains\Websites\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a Website is created from the Filament create form
 * and the operator opted into one or more cross-domain auto-spawns.
 *
 * The payment intent payload is optional — the Finance listener
 * short-circuits when it's null, and is idempotent via
 * external_ref = "website_setup:{websiteId}" so duplicate dispatch
 * (or a re-creation collision in a future migration script) cannot
 * produce two LOSS entries.
 *
 * Symmetric to DomainNames\Events\DomainRegistered — same shape,
 * same listener pattern, same idempotency discipline.
 */
final class WebsiteCreated
{
    use Dispatchable;

    /**
     * @param  array{
     *     amount_cents: int,
     *     currency?: string,
     *     paid_at?: \DateTimeInterface|string|null,
     *     method?: ?string,
     *     description?: ?string,
     * }|null  $paymentIntent
     */
    public function __construct(
        public readonly int $websiteId,
        public readonly ?array $paymentIntent = null,
    ) {}
}
