<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_fatture', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->unsignedBigInteger('client_contact_id')->index();

            // monthly | quarterly | yearly
            $table->string('frequency', 16)->default('monthly');

            // Lines blueprint — same shape as fatture.lines. Cloned to
            // each generated fattura on issuance.
            $this->jsonbOrJson($table, 'lines');

            $table->string('currency', 3)->default('EUR');

            // Day-of-month for monthly cadence. Nullable for quarterly
            // and yearly which use the next_issue_at date directly.
            $table->unsignedTinyInteger('day_of_month')->nullable();

            $table->date('next_issue_at')->index();
            $table->boolean('is_active')->default(true)->index();

            $table->date('last_issued_at')->nullable();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_fatture');
    }

    private function jsonbOrJson(Blueprint $table, string $column)
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? $table->jsonb($column)
            : $table->json($column);
    }
};
