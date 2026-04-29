<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();

            // Cal.com booking uid — globally unique, our idempotency key.
            $table->string('cal_event_id')->unique();

            $table->string('title');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->string('attendee_email')->nullable()->index();
            $table->string('status', 16)->default('pending')->index();

            // Raw payload from Cal.com so we can replay/inspect later.
            $this->jsonbOrJson($table, 'payload')->nullable();

            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }

    private function jsonbOrJson(Blueprint $table, string $column)
    {
        return Schema::getConnection()->getDriverName() === 'pgsql'
            ? $table->jsonb($column)
            : $table->json($column);
    }
};
