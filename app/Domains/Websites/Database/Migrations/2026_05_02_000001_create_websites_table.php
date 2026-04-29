<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->id();

            // Translatable: stored as {it: "...", en: "..."} via Spatie.
            $this->jsonbOrJson($table, 'name');
            $this->jsonbOrJson($table, 'notes')->nullable();

            $table->string('url');
            $table->string('status', 32)->default('active');

            // Scalar FK to the Contacts domain — never a belongsTo across
            // domain boundaries; resolve via ContactsService::find().
            $table->unsignedBigInteger('owner_contact_id')->nullable();

            $this->jsonbOrJson($table, 'tech_stack')->nullable();

            $table->date('subscription_started_at')->nullable();
            $table->date('next_renewal_at')->nullable();
            $table->unsignedSmallInteger('renewal_period_months')->default(12);

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('next_renewal_at');
            $table->index('owner_contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
    }

    private function jsonbOrJson(Blueprint $table, string $column)
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? $table->jsonb($column)
            : $table->json($column);
    }
};
