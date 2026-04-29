<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-year counter row for fattura sequential numbering.
 *
 * SELECT … FOR UPDATE on the row inside FatturaService::create() locks
 * out concurrent allocators; on Postgres this is a real row-level lock
 * via lockForUpdate(); on SQLite (test env only) the implicit
 * single-writer semantics provide the same guarantee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fattura_counters', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fattura_counters');
    }
};
