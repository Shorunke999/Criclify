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
        Schema::create('circles', function (Blueprint $table) {
            $table->id();
            $table->text('description')->nullable();
            $table->string('code');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->boolean('multiple_position')->default(false);
            $table->string('select_position_method')->default('sequence');
            $table->string('interval')->default('weekly');
            $table->string('status')->default('active');
            $table->integer('limit')->default(10);
            $table->string('current_cycle')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();

            $table->index(['creator_id', 'status']);
            $table->index(['rotation_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circles');
    }
};
