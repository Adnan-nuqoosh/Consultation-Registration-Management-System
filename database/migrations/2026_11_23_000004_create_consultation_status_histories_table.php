<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consultation_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained('consultation_requests')->cascadeOnDelete();
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->text('note')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->index(['consultation_request_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_status_histories');
    }
};
