<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('ragione_sociale')->nullable()->after('name');
            $table->string('sdi_code', 7)->nullable()->after('tax_code');
            $table->string('pec_email')->nullable()->after('sdi_code');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['ragione_sociale', 'sdi_code', 'pec_email']);
        });
    }
};
