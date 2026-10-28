<?php

declare(strict_types=1);

use App\Domains\Contacts\Models\Contact;
use App\Domains\Documents\Enums\PaymentStatus;
use App\Domains\Documents\Enums\RecurringFrequency;
use App\Domains\Documents\Models\Fattura;
use App\Domains\Documents\Models\RecurringFattura;
use App\Domains\Documents\Services\Public\PaymentsService;
use App\Domains\DomainNames\Enums\DomainStatus;
use App\Domains\DomainNames\Enums\Registrar;
use App\Domains\DomainNames\Models\DomainName;
use App\Domains\Finance\DTOs\CashFlowProjection;
use App\Domains\Finance\DTOs\ProjectedMonth;
use App\Domains\Finance\Filament\Pages\CashFlowProjectionPage;
use App\Domains\Finance\Models\RecurringExpense;
use App\Domains\Finance\Services\Public\CashFlowProjectionService;
use App\Domains\Websites\Models\Website;
use App\Models\User;
use App\Shared\CashFlow\CashFlowSchedule;
use App\Shared\CashFlow\ScheduledFlow;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

/**
 * Cash flow projection — expected income / loss per month, summed from
 * every domain's CashFlowSchedule, without writing any rows.
 */
function projectFrom(string $today, int $months = 12): CashFlowProjection
{
    return app(CashFlowProjectionService::class)->project(Carbon::parse($today), $months);
}

/**
 * @return list<array{string, string, int}> [date, income|loss, cents]
 */
function projectedFlows(CashFlowProjection $projection): array
{
    return array_map(
        fn (ScheduledFlow $flow) => [$flow->date->toDateString(), $flow->isIncome ? 'income' : 'loss', $flow->amount->cents],
        $projection->flows,
    );
}

function projectedMonth(CashFlowProjection $projection, string $key): ProjectedMonth
{
    return collect($projection->months)->sole(fn (ProjectedMonth $month) => $month->key() === $key);
}

function aRecurringInvoice(array $attributes): RecurringFattura
{
    return RecurringFattura::factory()->create([
        'client_contact_id' => Contact::factory()->create()->id,
        'frequency' => RecurringFrequency::Monthly->value,
        'day_of_month' => null,
        'is_active' => true,
        'currency' => 'EUR',
        'lines' => [['description' => 'Service', 'qty' => 1, 'unit_price_cents' => 10_000, 'vat_rate' => 0]],
        ...$attributes,
    ]);
}

function aDomain(array $attributes): DomainName
{
    return DomainName::query()->create([
        'name' => 'mysite.it',
        'registrar' => Registrar::Aruba->value,
        'status' => DomainStatus::Active->value,
        'renewal_period_months' => 12,
        'auto_renew' => true,
        'renewal_cost_cents' => 1_200,
        'currency' => 'EUR',
        ...$attributes,
    ]);
}

function aRecurringExpense(array $attributes): RecurringExpense
{
    return RecurringExpense::query()->create([
        'name' => 'Figma',
        'frequency' => RecurringFrequency::Monthly->value,
        'amount_cents' => 5_000,
        'currency' => 'EUR',
        'started_at' => '2025-01-01',
        'is_active' => true,
        ...$attributes,
    ]);
}

// ── Sources ──────────────────────────────────────────────────────────────

it('projects monthly recurring invoice cycles into a 12-month window', function () {
    aRecurringInvoice([
        'next_issue_at' => '2026-06-01',
        'lines' => [['description' => 'Hosting', 'qty' => 1, 'unit_price_cents' => 10_000, 'vat_rate' => 22]],
    ]);

    $projection = projectFrom('2026-05-01');

    // Window May 2026 → April 2027: cycles June … April, 11 × (€100 + 22% VAT).
    expect($projection->flows)->toHaveCount(11);
    expect($projection->income->cents)->toBe(11 * 12_200);
    expect($projection->net()->isNegative())->toBeFalse();
});

it('projects domain renewal losses at the expiry date and cycle', function () {
    aDomain(['expires_at' => '2026-08-15']);

    // The following cycle, 2027-08-15, falls outside the window.
    expect(projectedFlows(projectFrom('2026-05-01')))->toBe([['2026-08-15', 'loss', 1_200]]);
});

it('projects website hosting renewals on their cycle', function () {
    Website::factory()->create([
        'next_renewal_at' => '2026-09-15',
        'renewal_period_months' => 6,
        'renewal_cost_cents' => 3_000,
    ]);

    expect(projectedFlows(projectFrom('2026-05-01')))->toBe([
        ['2026-09-15', 'loss', 3_000],
        ['2027-03-15', 'loss', 3_000],
    ]);
});

it('projects recurring expenses as losses', function () {
    // Previously ignored: the projected net was overstated by every
    // subscription the studio itself pays for.
    aRecurringExpense(['next_due_at' => '2026-05-20']);

    expect(projectedFlows(projectFrom('2026-05-01', 3)))->toBe([
        ['2026-05-20', 'loss', 5_000],
        ['2026-06-20', 'loss', 5_000],
        ['2026-07-20', 'loss', 5_000],
    ]);
});

it('expects what clients still owe on issued invoices, on the due date', function () {
    $this->travelTo(Carbon::parse('2026-05-01'));
    $client = Contact::factory()->create()->id;
    $invoice = fn (int $cents, array $attributes) => Fattura::factory()->totalling($cents)->create(['client_contact_id' => $client, ...$attributes]);

    $invoice(12_200, ['issued_at' => '2026-05-01', 'due_date' => '2026-06-10']);
    $partial = $invoice(100_000, ['issued_at' => '2026-05-01', 'due_date' => '2026-07-01']);
    app(PaymentsService::class)->record($partial->id, ['amount_cents' => 40_000]);
    $paid = $invoice(9_000, ['issued_at' => '2026-05-01', 'due_date' => '2026-06-01']);
    app(PaymentsService::class)->record($paid->id, ['amount_cents' => 9_000]);
    $invoice(7_000, ['issued_at' => '2026-05-01', 'due_date' => '2026-06-01', 'payment_status' => PaymentStatus::Cancelled->value]);
    // No due date stated: the statutory 30-day payment term applies.
    $invoice(3_000, ['issued_at' => '2026-05-06', 'due_date' => null]);

    expect(projectedFlows(projectFrom('2026-05-01', 3)))->toBe([
        ['2026-06-05', 'income', 3_000],
        ['2026-06-10', 'income', 12_200],
        ['2026-07-01', 'income', 60_000],
    ]);
});

it('skips inactive schedules and renewals without a cost', function () {
    aRecurringInvoice(['next_issue_at' => '2026-06-01', 'is_active' => false]);
    aDomain(['name' => 'free.com', 'expires_at' => '2026-08-15', 'renewal_cost_cents' => null]);
    Website::factory()->create(['next_renewal_at' => '2026-08-15', 'renewal_period_months' => 12, 'renewal_cost_cents' => null]);
    aRecurringExpense(['next_due_at' => '2026-06-01', 'is_active' => false]);

    expect(projectFrom('2026-05-01')->isEmpty())->toBeTrue();
});

// ── Months and overdue items ─────────────────────────────────────────────

it('combines income and loss into a net per month with the right sign', function () {
    aRecurringInvoice(['next_issue_at' => '2026-06-01']); // €100 a month from June
    aDomain(['name' => 'expensive.com', 'expires_at' => '2026-07-15', 'renewal_cost_cents' => 50_000]);

    $projection = projectFrom('2026-05-01');

    expect($projection->net()->cents)->toBe(11 * 10_000 - 50_000);
    expect(projectedMonth($projection, '2026-07')->net()->cents)->toBe(-40_000);
});

it('carries everything still pending from before the window into its first month', function () {
    // On 15 May: the 10 Mar and 10 Apr cycles are not issued yet, and an
    // invoice due 20 Apr is unpaid. Both used to drop out of the picture.
    aRecurringInvoice(['next_issue_at' => '2026-03-10']);
    Fattura::factory()->totalling(5_000)->create([
        'client_contact_id' => Contact::factory()->create()->id,
        'issued_at' => '2026-03-20',
        'due_date' => '2026-04-20',
    ]);

    $projection = projectFrom('2026-05-15', 3);

    expect(projectedFlows($projection))->toBe([
        ['2026-03-10', 'income', 10_000],
        ['2026-04-10', 'income', 10_000],
        ['2026-04-20', 'income', 5_000],
        ['2026-05-10', 'income', 10_000],
        ['2026-06-10', 'income', 10_000],
        ['2026-07-10', 'income', 10_000],
    ]);
    expect(projectedMonth($projection, '2026-05')->income->cents)->toBe(35_000);
    expect(projectedMonth($projection, '2026-06')->income->cents)->toBe(10_000);
    expect(array_map($projection->isOverdue(...), $projection->flows))
        ->toBe([true, true, true, true, false, false]);
});

it('lands month-end schedules on the last day of short months instead of skipping them', function () {
    // Carbon's addMonth() takes Jan 31 to Mar 3: February was never billed.
    aRecurringInvoice(['next_issue_at' => '2026-01-31']);

    expect(array_column(projectedFlows(projectFrom('2026-01-01', 3)), 0))
        ->toBe(['2026-01-31', '2026-02-28', '2026-03-28']);
});

// ── Page ─────────────────────────────────────────────────────────────────

it('labels months and dates in the reader\'s language and flags overdue items', function () {
    $this->actingAs(User::factory()->create());
    $this->travelTo(Carbon::parse('2026-05-15'));
    app()->setLocale('it');
    aRecurringExpense(['next_due_at' => '2026-05-10']);

    // translatedFormat('MMM YYYY') read the ISO tokens as PHP ones:
    // "maggiomaggio5 2026202620262026".
    Livewire::test(CashFlowProjectionPage::class)
        ->assertSee('mag 2026')
        ->assertSee('10 mag 2026')
        ->assertSee(__('finance/projection.overdue'))
        ->assertSee(__('finance/projection.sources.recurring_expense'))
        ->assertDontSee('20262026');
});

it('computes the projection once per render', function () {
    $this->actingAs(User::factory()->create());
    $spy = new class implements CashFlowSchedule
    {
        public int $calls = 0;

        public function pending(CarbonImmutable $until): iterable
        {
            $this->calls++;

            return [];
        }
    };
    app()->instance('test.spy-schedule', $spy);
    app()->tag(['test.spy-schedule'], CashFlowSchedule::TAG);

    Livewire::test(CashFlowProjectionPage::class)->assertSee(__('finance/projection.empty'));

    expect($spy->calls)->toBe(1);
});
