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
       Schema::create('cooperatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users');
            $table->foreignId('country_id')->constrained();
            $table->string('organisation_name');
            $table->string('status')->default('pending');
            $table->string('organisation_type')->nullable();
            $table->string('organisation_reg_number')->nullable();
            $table->year('organisation_established_year')->nullable();

            $table->string('approx_member_number')->nullable();
            $table->boolean('has_existing_scheme')->default(false);

            $table->text('current_contribution_management')->nullable();
            $table->text('governance_structure')->nullable();

            // approval + compliance snapshot
            $table->json('intended_api_usage')->nullable();
            $table->boolean('organisation_handles_payments')->default(false);
            $table->boolean('has_internal_default_rules')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cooperatives');
    }
};
