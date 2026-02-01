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
        Schema::create('user_metas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('country_id')->nullable()->constrained();

            $table->string('role_in_org')->nullable();          // org contact person
            $table->string('experience')->nullable();           // creator experience
            $table->string('type_of_group')->nullable();        // creator
            $table->string('group_duration')->nullable();       // creator
            $table->boolean('can_enforce_rules_off_app')->nullable();
            // Referral
            $table->string('referral_code')->nullable()->unique();
            $table->unsignedInteger('referral_count')->default(0);

            //account details
            $table->string('account_number')->nullable();
            $table->string('alternate_account_number')->nullable();
            $table->string('bvn')->nullable();

            // Future-safe
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_metas');
    }
};
