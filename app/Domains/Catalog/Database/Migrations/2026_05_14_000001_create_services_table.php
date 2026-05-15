<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Service catalog. A reusable price list of the things the business
 * sells: picking a catalog row pre-fills a quotation / fattura line,
 * but the line owns its values from that point on — the catalog is a
 * template source, never authoritative over an already-issued bill.
 *
 * The default_* columns are exactly that: defaults copied into a line
 * at pick time. Editing a service later never rewrites past lines.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('category', 32)->index();   // ServiceCategory enum
            $table->text('description')->nullable();

            // Template values copied into a bill line on pick.
            $table->bigInteger('default_unit_price_cents')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->integer('default_vat_rate')->default(22);
            $table->string('default_cadence', 16)->nullable(); // LineCadence enum

            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);

            // Internal-only — never shown on the bill.
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
        Schema::dropIfExists('services');
    }
};
