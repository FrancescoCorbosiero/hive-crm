<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add renewal_cost_cents to websites so the Cash Flow Projection can
 * derive recurring hosting LOSS the same way it does for DomainName
 * renewals. Nullable: existing rows stay un-projected until the
 * operator fills the field in. Single-currency by convention — uses
 * config('app.currency') wherever it's read, matching the rest of the
 * Website flow (no separate `currency` column needed here).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->bigInteger('renewal_cost_cents')->nullable()->after('renewal_period_months');
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn('renewal_cost_cents');
        });
    }
};
