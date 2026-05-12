<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recurring expense subscriptions (hosting, SaaS, tools, …). Sibling
 * concept to `recurring_fatture`, but for cash-out instead of cash-in.
 *
 * Each row represents a standing arrangement that materializes into a
 * FinancialEntry (type=loss) at each cadence. The dashboard's Active
 * Subscriptions widget unions Websites + RecurringFattura (revenue)
 * with this table (cost) to give a single delay-aware view.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            // monthly | quarterly | yearly — matches RecurringFrequency.
            $table->string('frequency', 16)->default('monthly');

            $table->bigInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');

            // Free-form category that flows through to the materialized
            // FinancialEntry.category column.
            $table->string('category', 64)->nullable()->index();

            // Scalar FK to Contacts; resolved through ContactsService.
            $table->unsignedBigInteger('vendor_contact_id')->nullable()->index();

            $table->date('started_at');
            $table->date('next_due_at')->index();
            $table->date('last_logged_at')->nullable();

            $table->boolean('is_active')->default(true)->index();

            $table->text('notes')->nullable();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};
