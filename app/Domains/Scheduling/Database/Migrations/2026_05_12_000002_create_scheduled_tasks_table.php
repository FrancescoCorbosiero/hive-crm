<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->string('command');
            $table->string('cron_expression');
            $table->string('timezone')->nullable();
            $table->text('description')->nullable();

            $table->boolean('is_system')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->boolean('without_overlapping')->default(true);
            $table->boolean('on_one_server')->default(true);

            $table->timestamp('last_started_at')->nullable();
            $table->timestamp('last_finished_at')->nullable();
            $table->integer('last_exit_code')->nullable();
            $table->text('last_output')->nullable();

            $table->timestamps();

            $table->index('is_enabled');
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_tasks');
    }
};
