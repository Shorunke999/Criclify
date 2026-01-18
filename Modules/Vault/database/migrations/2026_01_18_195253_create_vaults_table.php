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
        Schema::create('vaults', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->decimal('total_amount')->default(0);
            $table->string('interval');
            $table->boolean('oweing')->default(false);
            $table->integer('no_of_save')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('maturity_date')->nullable();
            $table->timestamp('last_save')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaults');
    }
};
