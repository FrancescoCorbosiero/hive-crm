<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('vat_number', 32)->nullable();
            $table->string('tax_code', 32)->nullable();

            // jsonb on Postgres; falls back to TEXT on SQLite for tests.
            $this->jsonbOrJson($table, 'address')->nullable();
            $this->jsonbOrJson($table, 'roles')->default(json_encode([]));

            $table->text('notes')->nullable();
            $table->boolean('do_not_email')->default(false);

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('email');
            $table->index('owner_user_id');
            $table->index('do_not_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }

    private function jsonbOrJson(Blueprint $table, string $column)
    {
        $driver = Schema::getConnection()->getDriverName();

        return $driver === 'pgsql'
            ? $table->jsonb($column)
            : $table->json($column);
    }
};
