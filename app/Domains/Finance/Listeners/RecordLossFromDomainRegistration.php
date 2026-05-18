<?php

declare(strict_types=1);

namespace App\Domains\Finance\Listeners;

use App\Domains\DomainNames\Events\DomainRegistered;
use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Finance\Services\Public\FinanceService;
use Illuminate\Support\Carbon;

/**
 * When a domain is registered and the operator opted into "register
 * payment", mirror that cost as a LOSS FinancialEntry so the ledger
 * reflects the money actually spent on the registrar.
 *
 * Idempotent via external_ref = "domain_registration:{domainId}" —
 * the same registration cannot produce two loss entries even under
 * duplicate event delivery.
 *
 * Mirrors the legacy `logRenewalAction` flow on DomainNameResource
 * but for the initial registration cycle rather than a renewal.
 */
class RecordLossFromDomainRegistration
{
    public function __construct(private readonly FinanceService $finance) {}

    public function handle(DomainRegistered $event): void
    {
        if ($event->paymentIntent === null) {
            return;
        }

        $domain = DomainName::query()->find($event->domainId);
        if (! $domain) {
            return;
        }

        $externalRef = sprintf('domain_registration:%d', $domain->id);

        if ($this->finance->findIdByExternalRef($externalRef) !== null) {
            return;
        }

        $intent = $event->paymentIntent;
        $amountCents = (int) ($intent['amount_cents'] ?? 0);
        if ($amountCents <= 0) {
            return;
        }

        $occurredAt = isset($intent['paid_at']) && $intent['paid_at'] !== null
            ? Carbon::parse($intent['paid_at'])->toDateString()
            : ($domain->registered_at?->toDateString() ?? now()->toDateString());

        $this->finance->recordLoss([
            'amount_cents' => $amountCents,
            'currency' => $intent['currency'] ?? $domain->currency ?? config('app.currency', 'EUR'),
            'occurred_at' => $occurredAt,
            'description' => __('domain_names/labels.auto_populate.registration_description', [
                'name' => $domain->name,
                'registrar' => $domain->registrar->label(),
            ]),
            'category' => 'domains',
            'contact_id' => $domain->owner_contact_id,
            'external_ref' => $externalRef,
            'owner_user_id' => $domain->owner_user_id,
            'notes' => isset($intent['method']) && $intent['method'] !== null
                ? __('domain_names/labels.auto_populate.payment_method_note', ['method' => $intent['method']])
                : null,
        ]);
    }
}
