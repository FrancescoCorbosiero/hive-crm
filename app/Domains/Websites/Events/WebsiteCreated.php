<?php

declare(strict_types=1);

namespace App\Domains\Websites\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a Website is created from the Filament create form
 * and the operator opted into one or more cross-domain auto-spawns.
 *
 * Each intent payload is optional and independently idempotent in
 * its listener:
 *   - Finance:   external_ref = "website_setup:{websiteId}"
 *   - DomainNames: unique `name` constraint on domain_names + the
 *     scalar website_id back-link, so re-dispatch either no-ops or
 *     just re-links an existing matching domain.
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
     * @param  array{
     *     registrar: string,
     *     registered_at?: \DateTimeInterface|string|null,
     *     renewal_period_months?: ?int,
     *     name_override?: ?string,
     * }|null  $domainIntent
     */
    public function __construct(
        public readonly int $websiteId,
        public readonly ?array $paymentIntent = null,
        public readonly ?array $domainIntent = null,
    ) {}
}
