<?php

declare(strict_types=1);

namespace App\Domains\Finance\Listeners;

use App\Domains\Finance\Enums\FinancialEntrySource;
use App\Domains\Finance\Services\Public\FinanceService;
use App\Domains\Websites\Events\WebsiteCreated;
use App\Domains\Websites\Models\Website;
use Illuminate\Support\Carbon;

/**
 * When a Website is created and the operator opted into "register
 * setup / hosting cost", mirror that cost as a LOSS FinancialEntry
 * so the ledger reflects what was actually spent.
 *
 * Idempotent via external_ref = "website_setup:{websiteId}" — the
 * same website cannot produce two setup-cost entries even under
 * duplicate event delivery.
 *
 * The entry is tagged with FinancialEntrySource::Website + source_id,
 * so the existing analytics queries (incomeByWebsite, etc.) can
 * extend naturally to lossByWebsite without a schema change.
 */
class RecordLossFromWebsiteSetup
{
    public function __construct(private readonly FinanceService $finance) {}

    public function handle(WebsiteCreated $event): void
    {
        if ($event->paymentIntent === null) {
            return;
        }

        $website = Website::query()->find($event->websiteId);
        if (! $website) {
            return;
        }

        $externalRef = sprintf('website_setup:%d', $website->id);

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
            : ($website->subscription_started_at?->toDateString() ?? now()->toDateString());

        $description = (string) ($intent['description']
            ?? __('websites/labels.auto_populate.setup_description', [
                'name' => $website->getTranslation('name', app()->getLocale()) ?: $website->url,
            ]));

        $this->finance->recordLoss([
            'amount_cents' => $amountCents,
            'currency' => $intent['currency'] ?? config('app.currency', 'EUR'),
            'occurred_at' => $occurredAt,
            'description' => $description,
            'category' => 'hosting',
            'source_type' => FinancialEntrySource::Website->value,
            'source_id' => $website->id,
            'contact_id' => $website->owner_contact_id,
            'external_ref' => $externalRef,
            'owner_user_id' => $website->owner_user_id,
            'notes' => isset($intent['method']) && $intent['method'] !== null
                ? __('websites/labels.auto_populate.payment_method_note', ['method' => $intent['method']])
                : null,
        ]);
    }
}
