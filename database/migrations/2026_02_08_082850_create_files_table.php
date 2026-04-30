<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->uuid('uuid')->unique();
            $table->enum('status', ['failed', 'processing', 'completed'])->default('completed');
            $table->enum('visibility', ['private', 'public', 'shared'])->default('private');
            $table->boolean('is_scanned')->default(false);
            $table->string('disk', 20); // 'azure', 'gdrive', 'local', 'r2', 'supabase', 's3', etc

            // Metadata
            $table->string('category', 20);
            $table->string('extension', 40);
            $table->string('mime_type', 100);
            $table->string('name'); // Original file name - Slugged
            $table->unsignedMediumInteger('duration')->nullable(); // Audio & video; In seconds
            $table->unsignedBigInteger('bytes_size');
            
            $table->string('storage_name');
            $table->string('thumbnail_name')->nullable(); // For video
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            // $table->foreignId('folder_id')->nullable()->constrained('folders', 'id')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'category', 'deleted_at', 'created_at']);
            $table->index(['user_id', 'deleted_at']);
        });

        // Full-Text index
        // DB::statement('ALTER TABLE files ADD FULLTEXT fulltext_index (name)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
