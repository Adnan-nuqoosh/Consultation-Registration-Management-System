<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('consultation_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_request_id')->constrained('consultation_requests')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_notes');
    }
};
