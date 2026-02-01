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
        Schema::create('coop_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cooperative_id')->constrained()->unique();
            $table->decimal('cooperative_minimum_amount', 12, 2);
            $table->decimal('loan_multiplier', 5, 2);
            $table->string('repayment_frequency');
            $table->unsignedInteger('max_active_loan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coop_rules');
    }
};
