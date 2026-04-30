<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('year')->index();
            $table->unsignedInteger('number');

            $table->string('name');

            // Scalar FKs (no belongsTo across the boundary).
            $table->unsignedBigInteger('client_contact_id')->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();

            $table->date('issued_at');
            $table->date('valid_until')->nullable();

            $this->jsonbOrJson($table, 'lines');

            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('vat_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);
            $table->string('currency', 3)->default('EUR');

            $table->string('status', 16)->default('draft')->index();

            // Linkback to a generated PDF Document, if rendered.
            $table->foreignId('document_id')
                ->nullable()
                ->constrained('documents')
                ->nullOnDelete();

            // Linkback to the Fattura created on accept().
            $table->foreignId('fattura_id')
                ->nullable()
                ->constrained('fatture')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['year', 'number'], 'quotations_year_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }

    private function jsonbOrJson(Blueprint $table, string $column)
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? $table->jsonb($column)
            : $table->json($column);
    }
};
