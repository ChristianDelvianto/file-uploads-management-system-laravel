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
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('status', ['canceled', 'completed', 'failed', 'started'])->default('started');
            $table->string('disk', 20); // s3, r2, supabase, gdrive, etc

            // Metadata
            $table->string('category', 20);
            $table->string('extension', 40);
            $table->string('mime_type', 100);
            $table->string('name'); // Original file name
            $table->unsignedMediumInteger('duration')->nullable(); // Audio & video
            $table->unsignedBigInteger('bytes_size');
            $table->string('thumbnail_name')->nullable();
            $table->unsignedSmallInteger('last_chunk_index')->nullable();
            $table->unsignedSmallInteger('chunk_count');
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->timestamps();

            // Indexes
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
