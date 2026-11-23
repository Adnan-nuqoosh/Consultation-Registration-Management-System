<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('website_form_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('website_form_submission_id')->constrained('website_form_submissions')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['website_form_submission_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_form_activity_logs');
    }
};
