<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use  Modules\Vault\Enums\VaultStatusEnum;
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
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default(VaultStatusEnum::LOCKED->value);
            $table->decimal('total_amount', 18, 2);
            $table->decimal('interval_amount', 18, 2);

            $table->string('interval'); // daily, weekly, monthly
            $table->integer('duration'); // number of intervals

            $table->boolean('oweing')->default(false);

            $table->timestamp('start_date');
            $table->timestamp('maturity_date');
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
