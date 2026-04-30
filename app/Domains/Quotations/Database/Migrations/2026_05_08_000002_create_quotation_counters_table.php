<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-year sequential numbering counter — same pattern as fattura_counters.
 * Quotations don't legally need to be sequential, but a clean
 * "PREV-2026-0001" identifier makes them much easier to reference in
 * email + on the phone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_counters', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_counters');
    }
};
