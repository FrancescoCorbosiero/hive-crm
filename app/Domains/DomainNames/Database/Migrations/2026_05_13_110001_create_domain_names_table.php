<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain-name portfolio. Tracks every domain you've registered: which
 * registrar holds it, when it renews, what it costs you, and which
 * customer / website it belongs to.
 *
 * Cross-domain links are scalar FKs by design (owner_contact_id →
 * Contacts, website_id → Websites); resolution goes through those
 * domains' public services, never a belongsTo across the boundary.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_names', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();          // example.com
            $table->string('registrar', 32)->index();  // Registrar enum
            $table->string('status', 16)->default('active')->index();

            $table->date('registered_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->unsignedSmallInteger('renewal_period_months')->default(12);
            $table->boolean('auto_renew')->default(true);

            // Cost-only: what YOU pay the registrar per renewal cycle.
            $table->bigInteger('renewal_cost_cents')->nullable();
            $table->string('currency', 3)->default('EUR');

            // Scalar FKs — resolved via ContactsService / WebsitesService.
            $table->unsignedBigInteger('owner_contact_id')->nullable()->index();
            $table->unsignedBigInteger('website_id')->nullable()->index();

            // Free-form, e.g. nameservers, EPP notes, the actual provider
            // name when registrar = other.
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
        Schema::dropIfExists('domain_names');
    }
};
