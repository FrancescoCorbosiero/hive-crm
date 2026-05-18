<?php

declare(strict_types=1);

namespace App\Domains\DomainNames\Filament\Resources\DomainNameResource\Pages;

use App\Domains\DomainNames\Events\DomainRegistered;
use App\Domains\DomainNames\Filament\Resources\DomainNameResource;
use App\Domains\DomainNames\Models\DomainName;
use App\Domains\DomainNames\Services\Public\DomainNamesService;
use App\Shared\Filament\MoneyInput;
use Filament\Resources\Pages\CreateRecord;

class CreateDomainName extends CreateRecord
{
    protected static string $resource = DomainNameResource::class;

    /**
     * Pre-fill owner_contact_id from the ?owner_contact_id query
     * parameter, so the Contact 360 "Register Domain" quick-action
     * lands the operator on a form already tied to the right
     * customer.
     */
    public function mount(): void
    {
        parent::mount();

        $contactId = request()->query('owner_contact_id');
        if ($contactId !== null && is_numeric($contactId)) {
            $this->form->fill(array_merge($this->data, [
                'owner_contact_id' => (int) $contactId,
            ]));
        }
    }

    /**
     * Resolve the website / owner-contact links from the domain name
     * when the user left them blank.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(DomainNamesService::class)->autoLink($data);
    }

    /**
     * After the domain is created, fan out the operator's opt-in
     * intents to the relevant downstream domains via the
     * DomainRegistered event. The transient toggle fields are marked
     * dehydrated(false), so they live on $this->data (the full form
     * state) but never reach the model save.
     *
     * Each listener is independently idempotent — replaying this
     * event is safe.
     */
    protected function afterCreate(): void
    {
        /** @var DomainName $domain */
        $domain = $this->record;
        $raw = $this->data;

        $paymentIntent = null;
        if (! empty($raw['register_payment_enabled'])) {
            $cents = MoneyInput::majorToCents($raw['registration_cost_cents'] ?? null);
            if ($cents !== null && $cents > 0) {
                $paymentIntent = [
                    'amount_cents' => $cents,
                    'currency' => $domain->currency ?: config('app.currency', 'EUR'),
                    'paid_at' => $raw['registration_paid_at'] ?? null,
                    'method' => isset($raw['registration_method']) && $raw['registration_method'] !== ''
                        ? (string) $raw['registration_method']
                        : null,
                ];
            }
        }

        $websiteIntent = null;
        if (! empty($raw['create_website_enabled']) && ! empty($raw['new_website_url'])) {
            $websiteCostCents = MoneyInput::majorToCents($raw['new_website_cost_cents'] ?? null);
            $websiteIntent = [
                'url' => (string) $raw['new_website_url'],
                'name' => trim((string) ($raw['new_website_name'] ?? '')) ?: $domain->host(),
                'cost_amount_cents' => $websiteCostCents !== null && $websiteCostCents > 0
                    ? $websiteCostCents
                    : null,
                'cost_currency' => config('app.currency', 'EUR'),
                'cost_paid_at' => $raw['new_website_paid_at'] ?? null,
                'cost_method' => isset($raw['new_website_method']) && $raw['new_website_method'] !== ''
                    ? (string) $raw['new_website_method']
                    : null,
            ];
        }

        if ($paymentIntent !== null || $websiteIntent !== null) {
            DomainRegistered::dispatch($domain->id, $paymentIntent, $websiteIntent);
        }
    }
}
