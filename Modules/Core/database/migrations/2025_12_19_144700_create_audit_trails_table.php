<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
           $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // What happened
            $table->string('action'); // e.g. consent_given, kyc_submitted, kyc_approved
            $table->string('entity_type')->nullable(); // User, KycVerification, Circle
            $table->unsignedBigInteger('entity_id')->nullable();

            // Extra context
            $table->json('metadata')->nullable();

            // Request context
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Compliance
            $table->string('version')->nullable(); // NDPR v1.0, KYC policy v2.1

            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
