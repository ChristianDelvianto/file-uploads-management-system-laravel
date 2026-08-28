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
            $table->uuid('uuid');
            $table->enum('status', ['canceled', 'completed', 'failed', 'started'])->default('started');
            $table->string('disk', 100)->nullable(); // 'azure', 'gdrive', 'local', 'r2', 'supabase', 's3', etc
            $table->string('directory_path')->nullable();

            // Metadata
            $table->enum('category', ['audio', 'document', 'image', 'other', 'video']);
            $table->string('extension', 40);
            $table->string('mime_type', 100);
            $table->string('name'); // Original file name
            $table->unsignedInteger('duration')->nullable(); // Audio & video
            $table->unsignedBigInteger('bytes_size');

            $table->string('thumbnail_name')->nullable();
            $table->unsignedInteger('last_chunk_index')->nullable();
            $table->unsignedInteger('chunk_count');
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->timestamps();

            // Indexes
            $table->unique('uuid');
            $table->index('created_at'); // For clean up cronjob
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
