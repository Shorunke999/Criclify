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
        Schema::create('invite_compliances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // creator-specific
            $table->json('creator_context')->nullable();

            // cooperative-specific
            $table->json('organisation_context')->nullable();

            // compliance
            $table->boolean('not_a_bank_acknowledged');
            $table->boolean('no_fund_safeguard_acknowledged');
            $table->boolean('fixed_payout_acknowledged');
            $table->boolean('agree_to_terms');

            $table->text('additional_context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invite_compliances');
    }
};
