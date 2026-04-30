<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds due_date + paid_amount_cents to fatture so payment recording
 * has a target field and a roll-up cache. paid_amount_cents is the
 * authoritative cached sum; it's recomputed inside a transaction every
 * time a payment changes, so widgets don't need to aggregate the
 * payments table on every render.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fatture', function (Blueprint $table) {
            $table->date('due_date')->nullable()->index()->after('issued_at');
            $table->bigInteger('paid_amount_cents')->default(0)->after('total_cents');
        });
    }

    public function down(): void
    {
        Schema::table('fatture', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'paid_amount_cents']);
        });
    }
};
