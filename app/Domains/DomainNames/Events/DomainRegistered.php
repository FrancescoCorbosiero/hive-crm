<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a DomainName row is created and the operator opted
 * into one or more cross-domain auto-spawns from the create form.
 *
 * The two intent payloads are independent — either may be null. Each
 * listener short-circuits if its slice is absent, and every listener
 * is idempotent so duplicate dispatch is a no-op.
 *
 * Idempotency keys:
 *   - Finance: external_ref = "domain_registration:{domainId}"
 *   - Websites: domain_names.website_id (skip if already set)
 */
final class DomainRegistered
{
    use Dispatchable;

    /**
     * @param  array{
     *     amount_cents: int,
     *     currency?: string,
     *     paid_at?: \DateTimeInterface|string|null,
     *     method?: ?string,
     * }|null  $paymentIntent
     * @param  array{
     *     url: string,
     *     name?: ?string,
     * }|null  $websiteIntent
     */
    public function __construct(
        public readonly int $domainId,
        public readonly ?array $paymentIntent = null,
        public readonly ?array $websiteIntent = null,
    ) {}
}
