<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consultation_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 32)->nullable()->unique();
            $table->string('full_name', 150);
            $table->string('company', 180);
            $table->string('job_title', 150);
            $table->string('email', 190)->index();
            $table->string('phone', 40);
            $table->json('product_group');
            $table->string('export_status', 100);
            $table->text('support_needed');
            $table->string('preferred_consultation_time', 120);
            $table->boolean('privacy_consent')->default(false);
            $table->string('status', 40)->default('New')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_requests');
    }
};
