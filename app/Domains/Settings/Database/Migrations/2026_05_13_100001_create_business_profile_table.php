<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Singleton table holding the owner's identity (Cedente block on every
 * FatturaPA XML the app generates, plus header data for PDF templates).
 *
 * Single row by convention (id = 1). The application keeps this
 * invariant via BusinessProfileService — never directly creating
 * additional rows.
 *
 * Bank accounts live in a jsonb column (small N, repeater UX preferred
 * over a separate relational table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profile', function (Blueprint $table) {
            $table->id();

            // ── Anagrafica ────────────────────────────────────────
            $table->string('type', 32)->default('ditta_individuale');
            $table->string('denominazione');
            $table->string('nome')->nullable();
            $table->string('cognome')->nullable();
            $table->string('codice_fiscale', 32)->nullable();
            $table->string('partita_iva', 32)->nullable();
            $table->string('regime_fiscale', 8)->default('RF19');
            $table->string('natura_default', 8)->nullable();

            // ── Sede ──────────────────────────────────────────────
            $table->string('sede_indirizzo')->nullable();
            $table->string('sede_civico', 16)->nullable();
            $table->string('sede_cap', 16)->nullable();
            $table->string('sede_comune')->nullable();
            $table->string('sede_provincia', 4)->nullable();
            $table->string('sede_nazione', 4)->default('IT');

            // ── Contatti ──────────────────────────────────────────
            $table->string('email')->nullable();
            $table->string('pec_email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('website_url')->nullable();

            // ── Conti correnti ────────────────────────────────────
            // [{name, iban, bic, bank_name, account_holder, is_primary, notes}]
            $this->jsonbOrJson($table, 'bank_accounts')->nullable();

            // ── Note libere ───────────────────────────────────────
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profile');
    }

    private function jsonbOrJson(Blueprint $table, string $column)
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? $table->jsonb($column)
            : $table->json($column);
    }
};
