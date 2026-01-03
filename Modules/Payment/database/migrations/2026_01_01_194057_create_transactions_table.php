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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('circle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();

            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('NGN');

            $table->string('type');   // contribution, payout, refund
            $table->json('type_ids')->nullable();
            $table->string('status'); // pending, success, failed
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
