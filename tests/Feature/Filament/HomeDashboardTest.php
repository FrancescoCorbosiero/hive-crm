<?php

declare(strict_types=1);

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Models\RecurringFattura;
use App\Domains\Finance\Models\RecurringExpense;
use App\Domains\Quotations\Enums\QuotationStatus;
use App\Domains\Quotations\Models\Quotation;
use App\Domains\Websites\Models\Website;
use App\Filament\Pages\HomeDashboard;
use App\Filament\Widgets\ActiveSubscriptionsWidget;
use App\Filament\Widgets\DashboardKpisWidget;
use App\Filament\Widgets\OpenQuotationsWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\WelcomeHeroWidget;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('renders the home dashboard', function () {
    Livewire::test(HomeDashboard::class)->assertSuccessful();
});

it('renders the welcome hero widget', function () {
    Livewire::test(WelcomeHeroWidget::class)->assertSuccessful();
});

it('renders the custom KPI strip widget', function () {
    Livewire::test(DashboardKpisWidget::class)->assertSuccessful();
});

it('renders the quick-actions widget', function () {
    Livewire::test(QuickActionsWidget::class)->assertSuccessful();
});

it('flags only quotations that expire within a week', function (int $daysLeft, string $expected, string $notExpected) {
    // Carbon 3 signs diffInDays(): every future date came out negative,
    // so every open quotation was flagged as expiring.
    Quotation::factory()->create([
        'client_contact_id' => Contact::factory()->create()->id,
        'status' => QuotationStatus::Sent->value, // "info" badge: no clash
        'valid_until' => now()->addDays($daysLeft),
    ]);

    Livewire::test(OpenQuotationsWidget::class)
        ->assertSeeHtml($expected)
        ->assertDontSeeHtml($notExpected);
})->with([
    'valid for two months' => [60, 'fi-color-success', 'fi-color-warning'],
    'expiring in three days' => [3, 'fi-color-warning', 'fi-color-success'],
]);

it('names subscription counterparties without a query per row', function () {
    $rows = function (int $n): void {
        foreach (range(1, $n) as $i) {
            $contact = Contact::factory()->create();
            Website::factory()->create(['owner_contact_id' => $contact->id, 'next_renewal_at' => now()->addMonth()]);
            RecurringFattura::factory()->create(['client_contact_id' => $contact->id]);
            RecurringExpense::query()->create([
                'name' => 'Tool '.$i,
                'frequency' => 'monthly',
                'amount_cents' => 1_000,
                'started_at' => now(),
                'next_due_at' => now()->addWeek(),
                'vendor_contact_id' => $contact->id,
            ]);
        }
    };
    $queries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(ActiveSubscriptionsWidget::class);

        return count(DB::getQueryLog());
    };

    $rows(2);
    $few = $queries();
    $rows(8);

    expect($queries())->toBe($few);
    expect(Livewire::test(ActiveSubscriptionsWidget::class)->instance()->rows->pluck('counterparty')->filter())
        ->toHaveCount(30);
});
