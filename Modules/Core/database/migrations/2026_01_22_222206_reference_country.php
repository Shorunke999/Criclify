<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('reference_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 3); // NGN
            $table->string('symbol')->nullable();
            $table->timestamps();
        });
        Schema::create('reference_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso_code', 2)->unique(); // NG
            $table->string('phone_code')->nullable();
            $table->foreignId('reference_currency_id')->constrained('reference_currencies');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('reference_currencies');
        Schema::dropIfExists('reference_countries');
    }
};
