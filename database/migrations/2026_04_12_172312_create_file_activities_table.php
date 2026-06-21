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
        Schema::create('file_activities', function (Blueprint $table) {
            $table->id();
            $table->enum('action', ['download', 'open']);
            $table->string('ip_address', 45);
            $table->string('user_agent');
            $table->foreignId('file_id')->constrained('files', 'id')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            // Indexes (The indexes and composite indexes focuses on user-facing features only)
            $table->index(['file_id', 'action', 'created_at']);
            $table->index(['file_id', 'ip_address', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_activities');
    }
};
