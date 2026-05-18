<?php

declare(strict_types=1);

namespace App\Domains\Websites\Filament\Resources\WebsiteResource\Pages;

use App\Domains\Websites\Events\WebsiteCreated;
use App\Domains\Websites\Filament\Resources\WebsiteResource;
use App\Domains\Websites\Models\Website;
use App\Shared\Filament\MoneyInput;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateWebsite extends CreateRecord
{
    use Translatable;

    protected static string $resource = WebsiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
        ];
    }

    /**
     * Fan out the operator's opt-in auto-spawns via the WebsiteCreated
     * event. The transient toggle / inline fields are dehydrated(false),
     * so they live on $this->data (full form state) but never reach the
     * model. The Finance listener is independently idempotent.
     */
    protected function afterCreate(): void
    {
        /** @var Website $website */
        $website = $this->record;
        $raw = $this->data;

        $paymentIntent = null;
        if (! empty($raw['register_cost_enabled'])) {
            $cents = MoneyInput::majorToCents($raw['setup_cost_cents'] ?? null);
            if ($cents !== null && $cents > 0) {
                $paymentIntent = [
                    'amount_cents' => $cents,
                    'currency' => config('app.currency', 'EUR'),
                    'paid_at' => $raw['setup_paid_at'] ?? null,
                    'method' => isset($raw['setup_method']) && $raw['setup_method'] !== ''
                        ? (string) $raw['setup_method']
                        : null,
                ];
            }
        }

        if ($paymentIntent !== null) {
            WebsiteCreated::dispatch($website->id, $paymentIntent);
        }
    }
}
