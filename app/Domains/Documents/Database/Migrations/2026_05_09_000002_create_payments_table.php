<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fattura_id')
                ->constrained('fatture')
                ->cascadeOnDelete();

            $table->date('paid_at')->index();
            $table->bigInteger('amount_cents');
            $table->string('currency', 3)->default('EUR');

            $table->string('method', 32)->default('bank_transfer')->index();

            // External reference — bank transaction id, Stripe charge,
            // PayPal txn, cheque number. Free text on purpose.
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            // Linkback to the auto-created Finance Transaction so we
            // can clean up (or re-sync) if the payment is deleted.
            $table->unsignedBigInteger('transaction_id')->nullable();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['fattura_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
