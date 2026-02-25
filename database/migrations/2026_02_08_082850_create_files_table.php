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
            
            // Metadata
            $table->string('category', 20);
            $table->string('extension', 40);
            $table->string('mime_type', 100);
            $table->string('name');
            $table->unsignedBigInteger('size', false);
            
            $table->string('thumbnail_path')->nullable(); // For video
            $table->string('storage_path');
            $table->string('disk', 20); // s3, r2, supabase, gdrive
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes

            // User-Pattern
            $table->index(['user_id', 'deleted_at', 'updated_at']);
            $table->index(['user_id', 'category', 'deleted_at', 'updated_at']);
            $table->index(['user_id', 'name', 'deleted_at', 'updated_at']);

            // Admin-Pattern (Global)
            $table->index(['deleted_at', 'updated_at']);
            $table->index(['size', 'deleted_at']);
            // $table->index(['category', 'deleted_at', 'updated_at']);
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
