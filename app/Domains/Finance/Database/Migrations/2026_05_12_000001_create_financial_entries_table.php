<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduce the FinancialEntry proxy table — a domain-agnostic
 * INCOME/LOSS ledger that the Finance domain owns. Existing
 * `transactions` rows are migrated 1:1 (with `expense` → `loss`)
 * and the legacy table is dropped.
 *
 * Also renames `payments.transaction_id` → `payments.financial_entry_id`
 * because that column now references the new table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();

            $table->string('type', 16)->index();            // income|loss
            $table->bigInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');

            $table->date('occurred_at')->index();
            $table->string('description');
            $table->string('category', 64)->nullable()->index();

            // Polymorphic source: alias from FinancialEntrySource enum
            // + int id. No morphTo — the owning domain handles lookups.
            $table->string('source_type', 32)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->unsignedBigInteger('contact_id')->nullable()->index();

            $table->text('notes')->nullable();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'fe_source_idx');
            $table->index(['type', 'occurred_at'], 'fe_type_date_idx');
        });

        // Backfill from the legacy table if it exists (skipped on fresh installs).
        if (Schema::hasTable('transactions')) {
            DB::statement(<<<'SQL'
                INSERT INTO financial_entries (
                    id, type, amount_cents, currency, occurred_at, description,
                    category, source_type, source_id, contact_id, notes,
                    owner_user_id, created_at, updated_at
                )
                SELECT
                    id,
                    CASE type WHEN 'expense' THEN 'loss' ELSE type END AS type,
                    amount_cents, currency, occurred_at, description,
                    category, source_type, source_id, contact_id, notes,
                    owner_user_id, created_at, updated_at
                FROM transactions
            SQL);

            Schema::drop('transactions');
        }

        if (Schema::hasColumn('payments', 'transaction_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->renameColumn('transaction_id', 'financial_entry_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payments', 'financial_entry_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->renameColumn('financial_entry_id', 'transaction_id');
            });
        }

        // Recreate the legacy table and copy data back so a rollback is
        // not destructive.
        if (! Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->string('type', 16)->index();
                $table->bigInteger('amount_cents');
                $table->string('currency', 3)->default('EUR');
                $table->date('occurred_at')->index();
                $table->string('description');
                $table->string('category', 64)->nullable()->index();
                $table->string('source_type', 32)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->text('notes')->nullable();
                $table->foreignId('owner_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps();
                $table->index(['source_type', 'source_id'], 'tx_source_idx');
                $table->index(['type', 'occurred_at'], 'tx_type_date_idx');
            });

            DB::statement(<<<'SQL'
                INSERT INTO transactions (
                    id, type, amount_cents, currency, occurred_at, description,
                    category, source_type, source_id, contact_id, notes,
                    owner_user_id, created_at, updated_at
                )
                SELECT
                    id,
                    CASE type WHEN 'loss' THEN 'expense' ELSE type END AS type,
                    amount_cents, currency, occurred_at, description,
                    category, source_type, source_id, contact_id, notes,
                    owner_user_id, created_at, updated_at
                FROM financial_entries
            SQL);
        }

        Schema::dropIfExists('financial_entries');
    }
};
