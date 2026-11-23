<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consultation_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained('consultation_requests')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['consultation_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_activity_logs');
    }
};
