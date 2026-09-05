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
        Schema::create('file_scans', function (Blueprint $table) {
            $table->foreignId('file_id');
            $table->enum('status', ['clean', 'infected', 'none', 'pending', 'processing'])->default('pending');

            // For traceability and debugging purpose, we can store the error message if any
            $table->text('error_message')->nullable();
            $table->timestamp('last_scan_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->primary('file_id');
            $table->index(['status', 'last_scan_at']); // For internal usage only, (if we develop it)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_scans');
    }
};
