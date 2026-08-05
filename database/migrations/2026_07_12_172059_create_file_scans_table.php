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
            $table->timestamps();

            // Indexes
            $table->primary('file_id');
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
