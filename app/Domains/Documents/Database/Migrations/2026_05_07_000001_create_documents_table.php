<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            // Translatable: jsonb on Postgres, json on SQLite.
            $this->jsonbOrJson($table, 'title');

            $table->string('category', 32)->default('other')->index();
            $table->string('file_path');
            $table->string('disk', 32)->default('s3');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime', 128)->nullable();

            // Polymorphic backlink (typed alias + integer id; never a
            // morphTo to a class outside this domain).
            $table->string('related_type', 32)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->index(['related_type', 'related_id'], 'doc_related_idx');

            $table->date('issued_at')->nullable()->index();

            $this->jsonbOrJson($table, 'metadata')->nullable();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }

    private function jsonbOrJson(Blueprint $table, string $column)
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? $table->jsonb($column)
            : $table->json($column);
    }
};
