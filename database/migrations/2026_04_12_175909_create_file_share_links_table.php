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
        Schema::create('file_share_links', function (Blueprint $table) {
            $table->id();
            $table->string('share_id', 64);
            $table->foreignId('file_id')->constrained('files', 'id')->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->unique('share_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_share_links');
    }
};
