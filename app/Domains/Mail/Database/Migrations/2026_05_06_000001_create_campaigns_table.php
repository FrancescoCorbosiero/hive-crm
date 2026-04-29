<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            // Translatable: stored as {it: "...", en: "..."} via Spatie.
            $this->jsonbOrJson($table, 'subject');
            $this->jsonbOrJson($table, 'body_html');

            $table->string('status', 16)->default('draft')->index();

            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();

            // Aggregate stats — kept on the row so widgets don't aggregate
            // millions of recipient rows on every render.
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('bounced_count')->default(0);
            $table->unsignedInteger('complained_count')->default(0);
            $table->unsignedInteger('opened_count')->default(0);
            $table->unsignedInteger('clicked_count')->default(0);
            $table->unsignedInteger('unsubscribed_count')->default(0);

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }

    private function jsonbOrJson(Blueprint $table, string $column)
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? $table->jsonb($column)
            : $table->json($column);
    }
};
