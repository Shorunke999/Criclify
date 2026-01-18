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
         Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circle_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

            $table->decimal('balance', 18, 2)->default(0);
            $table->string('type');
            $table->string('currency', 3)->default('NGN');

            $table->timestamps();

            $table->unique('circle_id');
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
