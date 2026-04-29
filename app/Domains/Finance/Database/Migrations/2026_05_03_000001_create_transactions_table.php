<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('type', 16)->index();           // income|expense
            $table->bigInteger('amount_cents');             // signed integer
            $table->string('currency', 3)->default('EUR');

            $table->date('occurred_at')->index();
            $table->string('description');
            $table->string('category', 64)->nullable()->index();

            // Polymorphic source: alias from TransactionSource enum + int id.
            // We deliberately do NOT define a morphTo() — the source domain
            // owns its lookups via its public service.
            $table->string('source_type', 32)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            // Scalar FK to Contacts; resolved via ContactsService::find().
            $table->unsignedBigInteger('contact_id')->nullable()->index();

            $table->text('notes')->nullable();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'tx_source_idx');
            $table->index(['type', 'occurred_at'], 'tx_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
