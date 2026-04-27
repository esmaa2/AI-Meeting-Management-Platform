<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('analysis_profile')->default('executive_summary');
            // executive_summary | action_oriented | verbatim_archive
            $table->text('transcript')->nullable();
            $table->text('ai_summary')->nullable();
            $table->string('audio_file_path')->nullable();
            $table->string('audio_file_name')->nullable();
            $table->unsignedBigInteger('audio_file_size')->nullable(); // bytes
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('word_count')->nullable();
            $table->string('sentiment')->nullable(); // productive | informational | decision_focused
            $table->string('status')->default('processing'); // processing | ready | failed
            $table->string('department')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};