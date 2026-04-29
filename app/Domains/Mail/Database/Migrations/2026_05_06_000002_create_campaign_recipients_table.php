<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                ->constrained('campaigns')
                ->cascadeOnDelete();

            // Scalar FK to Contacts (no belongsTo across the boundary).
            $table->unsignedBigInteger('contact_id');

            $table->string('status', 16)->default('pending')->index();
            $table->dateTime('sent_at')->nullable();

            // Returned by SES on send; primary key for matching SNS
            // bounce/complaint/delivery/open/click events back to a recipient.
            $table->string('ses_message_id')->nullable()->index();

            $table->timestamps();

            // Each contact appears at most once per campaign.
            $table->unique(['campaign_id', 'contact_id'], 'cr_campaign_contact_unique');
            $table->index(['campaign_id', 'status'], 'cr_campaign_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
