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
        Schema::create('circle_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circle_id')->constrained('circles')->cascadeOnDelete();
            $table->foreignId('circle_member_id')->constrained('circle_members')->cascadeOnDelete();
            $table->unsignedInteger('cycle_index')->default(0);
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('status'); // pending, paid, overdue
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();

            // Payment integration
            $table->foreignId('transaction_id')->nullable();
            $table->timestamps();

            $table->unique(['circle_member_id', 'due_date']);
            $table->index(['status', 'due_date','cycle_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circle_contributions');
    }
};
