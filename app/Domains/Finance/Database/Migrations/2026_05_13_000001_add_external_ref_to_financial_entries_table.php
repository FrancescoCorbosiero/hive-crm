<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stable external identity for FinancialEntry rows that mirror an
 * outside artefact. Today's only consumer is the FatturaPA importer
 * routing inbound (purchase) invoices to FinancialEntry(loss): the
 * ref shape is `fpa:{cedente_piva}:{year}:{number}` so re-importing
 * the same XML twice is an idempotent no-op rather than two losses.
 *
 * The unique index is partial (only when external_ref IS NOT NULL)
 * so existing untagged entries don't conflict with each other.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->string('external_ref', 191)->nullable()->after('notes');
        });

        // Postgres supports partial indexes; SQLite doesn't, but the
        // unique index on a nullable column there is effectively
        // partial because NULLs aren't equal to NULLs.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            Schema::table('financial_entries', function (Blueprint $table) {
                $table->index('external_ref', 'financial_entries_external_ref_idx');
            });
            \Illuminate\Support\Facades\DB::statement(
                'CREATE UNIQUE INDEX financial_entries_external_ref_unique '
                .'ON financial_entries (external_ref) WHERE external_ref IS NOT NULL',
            );
        } else {
            Schema::table('financial_entries', function (Blueprint $table) {
                $table->unique('external_ref', 'financial_entries_external_ref_unique');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement(
                'DROP INDEX IF EXISTS financial_entries_external_ref_unique',
            );
            Schema::table('financial_entries', function (Blueprint $table) {
                $table->dropIndex('financial_entries_external_ref_idx');
            });
        } else {
            Schema::table('financial_entries', function (Blueprint $table) {
                $table->dropUnique('financial_entries_external_ref_unique');
            });
        }

        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropColumn('external_ref');
        });
    }
};
