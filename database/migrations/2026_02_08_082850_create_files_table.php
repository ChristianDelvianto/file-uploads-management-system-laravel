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

            /**
             * Additionaly, we can separate these columns below into separate table:
             * 
             * 1. To support multi disks
             * 2. Internal analytics and also when moving / copying file object to different disks
             */
            $table->string('disk', 100); // 'azure', 'gdrive', 'local', 'r2', 'supabase', 's3', etc
            $table->string('directory_path');
            $table->enum('category', ['audio', 'document', 'image', 'other', 'video']);
            $table->string('extension', 40);
            $table->string('mime_type', 100);
            $table->string('name'); // Original name (Editable)
            $table->unsignedMediumInteger('duration')->nullable(); // Audio & Video (In seconds)
            $table->unsignedBigInteger('bytes_size');
            $table->string('storage_name'); // Immutable (For internal storage name)
            $table->string('thumbnail_name')->nullable(); // For video

            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique('uuid');
            $table->index(['user_id', 'deleted_at', 'created_at', 'id']); // User files
            $table->index(['user_id', 'category', 'deleted_at', 'created_at', 'id']); // User specific files
            $table->index(['user_id', 'bytes_size', 'deleted_at']); // User trash and delete all trashed
            $table->index('deleted_at'); // For clean up cron job
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
