<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Qualification metadata used during the discovery / pricing phase of
 * a deal. None of these are required to issue a quotation, but having
 * them lets the dashboard rank, segment and filter leads sensibly
 * (e.g. "high-budget e-commerce projects" or "redesign jobs only").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('social_url')->nullable()->after('company_name');
            $table->boolean('is_redesign')->default(false)->after('social_url');
            $table->string('budget_tier', 16)->nullable()->index()->after('is_redesign');
            $table->string('business_category', 32)->nullable()->index()->after('budget_tier');
            $table->string('website_type', 16)->nullable()->index()->after('business_category');
            $table->boolean('is_estero')->default(false)->after('website_type');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'social_url', 'is_redesign', 'budget_tier',
                'business_category', 'website_type', 'is_estero',
            ]);
        });
    }
};
