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
       Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('job_id')->unique();
            $table->string('smile_job_id')->nullable();
            $table->string('country', 2);
            $table->string('id_type');
            $table->string('status')->nullable(); // pending, approved, rejected, failed
            $table->string('result_code')->nullable();
            $table->string('result_text')->nullable();
            $table->json('actions')->nullable();
            $table->json('personal_info')->nullable();
            $table->text('document_image')->nullable(); // Store base64 or S3 URL
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
