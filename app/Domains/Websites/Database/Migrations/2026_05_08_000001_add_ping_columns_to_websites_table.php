<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->boolean('is_up')->nullable()->index();
            $table->unsignedSmallInteger('last_status_code')->nullable();
            $table->dateTime('last_pinged_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('websites', function (Blueprint $table) {
            $table->dropColumn(['is_up', 'last_status_code', 'last_pinged_at']);
        });
    }
};
