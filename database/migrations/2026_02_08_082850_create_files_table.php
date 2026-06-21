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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->enum('status', ['completed', 'failed', 'processing'])->default('completed');
            $table->enum('visibility', ['private', 'public', 'shared'])->default('private');
            $table->enum('scan_status', ['clean', 'infected', 'none', 'pending'])->default('pending');
            $table->string('disk', 100); // 'azure', 'gdrive', 'local', 'r2', 'supabase', 's3', etc
            $table->string('directory_path');

            // Metadata
            $table->enum('category', ['audio', 'document', 'image', 'other', 'video']);
            $table->string('extension', 40);
            $table->string('mime_type', 100);
            $table->string('name'); // Original name
            $table->unsignedMediumInteger('duration')->nullable(); // Audio & video; In seconds
            $table->unsignedBigInteger('bytes_size');

            $table->string('storage_name');
            $table->string('thumbnail_name')->nullable(); // For video
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes (The indexes and composite indexes focuses on user-facing features only)
            $table->unique('uuid');
            $table->index(['user_id', 'category', 'created_at', 'deleted_at']);
            $table->index(['user_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
