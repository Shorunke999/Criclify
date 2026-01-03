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
        Schema::create('circle_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circle_id')->constrained('circles')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('position')->dafault(0);
            $table->integer('no_of_times')->default(1);
            $table->string('paid_status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['circle_id', 'user_id']);
            $table->index(['circle_id', 'position']);
            $table->index(['circle_id', 'paid_status']);
            $table->index(['user_id', 'paid_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_members');
    }
};
