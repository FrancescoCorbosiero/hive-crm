<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fatture', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('year')->index();
            $table->unsignedInteger('number');

            // Scalar FK to Contacts.
            $table->unsignedBigInteger('client_contact_id')->index();

            $table->date('issued_at');

            // Lines as jsonb so future SdI exporter has the source-of-truth
            // structured data, not just rendered totals.
            $this->jsonbOrJson($table, 'lines');

            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('vat_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);
            $table->string('currency', 3)->default('EUR');

            $table->string('payment_status', 32)->default('unpaid')->index();

            // Linkback to the rendered Document row.
            $table->foreignId('document_id')
                ->nullable()
                ->constrained('documents')
                ->nullOnDelete();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // (year, number) is the canonical fattura identifier — the
            // unique constraint is the second line of defence behind
            // the counter row's lockForUpdate.
            $table->unique(['year', 'number'], 'fatture_year_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fatture');
    }

    private function jsonbOrJson(Blueprint $table, string $column)
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? $table->jsonb($column)
            : $table->json($column);
    }
};
