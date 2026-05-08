<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->timestamp('last_contacted_at')->nullable()->index()->after('next_action_at');
            $table->string('lost_reason', 64)->nullable()->after('last_contacted_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'last_contacted_at', 'lost_reason']);
        });
    }
};
