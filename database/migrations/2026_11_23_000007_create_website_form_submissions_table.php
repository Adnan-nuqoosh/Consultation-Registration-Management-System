<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('website_form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_number', 32)->unique();
            $table->string('form_type', 24)->index();
            $table->string('name', 180)->nullable()->index();
            $table->string('company', 180)->nullable()->index();
            $table->string('email', 190)->nullable()->index();
            $table->string('phone', 40)->nullable();
            $table->json('payload');
            $table->string('status', 40)->default('New')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['form_type', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_form_submissions');
    }
};
