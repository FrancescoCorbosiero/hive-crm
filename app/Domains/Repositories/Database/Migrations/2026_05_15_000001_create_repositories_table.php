<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Source-code repositories you maintain. A read-only outward link —
 * no API calls, no commits/issues sync; Hive holds the URL and the
 * cross-domain scalar FKs (owner_contact_id, website_id) so the repo
 * surfaces alongside the rest of the customer / website context.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();

            // Human label, e.g. "francescocorbosiero/hive-crm".
            $table->string('name');
            $table->string('url')->unique();
            $table->string('provider', 16)->default('github')->index();

            // Cross-domain links — scalar FKs, resolved via the public
            // services of those domains. Mirrors DomainNames.
            $table->unsignedBigInteger('owner_contact_id')->nullable()->index();
            $table->unsignedBigInteger('website_id')->nullable()->index();

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
        Schema::dropIfExists('repositories');
    }
};
