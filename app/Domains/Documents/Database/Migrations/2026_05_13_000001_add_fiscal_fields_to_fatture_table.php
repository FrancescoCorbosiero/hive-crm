<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FatturaPA XML export needs to know the document's fiscal regime
 * (RF01 ordinary, RF19 forfettario, …) and, when VAT is exempt, the
 * Natura code that justifies the exemption (N2.2 for forfettario,
 * N1–N7 for the other cases). The Natura must also appear on every
 * VAT=0 line. We default to the cedente's configured regime so
 * existing rows light up automatically after migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fatture', function (Blueprint $table) {
            $default = (string) (config('fattura.cedente.regime_fiscale') ?: 'RF19');
            $table->string('regime_fiscale', 4)->default($default)->after('payment_status');
            $table->string('natura', 4)->nullable()->after('regime_fiscale');
        });
    }

    public function down(): void
    {
        Schema::table('fatture', function (Blueprint $table) {
            $table->dropColumn(['regime_fiscale', 'natura']);
        });
    }
};
