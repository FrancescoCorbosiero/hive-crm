<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks an entry as "earned by the business" (taxable) vs. "received but
 * not earned" (donations, grants, personal contributions). All existing
 * rows are taxable; analytics queries filter on this column by default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->boolean('is_taxable')->default(true)->after('category')->index();
        });
    }

    public function down(): void
    {
        Schema::table('financial_entries', function (Blueprint $table) {
            $table->dropIndex(['is_taxable']);
            $table->dropColumn('is_taxable');
        });
    }
};
