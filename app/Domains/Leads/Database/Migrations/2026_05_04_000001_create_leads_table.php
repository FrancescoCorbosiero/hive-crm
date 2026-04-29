<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();

            $table->string('source', 32)->nullable()->index();
            $table->string('status', 32)->default('new')->index();

            $table->bigInteger('estimated_value_cents')->nullable();
            $table->string('estimated_value_currency', 3)->default('EUR');

            $table->text('notes')->nullable();
            $table->dateTime('next_action_at')->nullable()->index();

            // Set when the lead is converted into a Contact (scalar FK).
            $table->unsignedBigInteger('converted_contact_id')->nullable();
            $table->timestamp('converted_at')->nullable();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
