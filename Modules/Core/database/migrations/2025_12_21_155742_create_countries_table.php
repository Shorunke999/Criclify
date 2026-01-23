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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso_code', 2)->unique(); // NG
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            // Fees (percentages)
            $table->decimal('platform_fee_percentage', 5, 2)->default(0.00);
            $table->decimal('circle_creation_fee_percentage', 5, 2)->default(0.00);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
