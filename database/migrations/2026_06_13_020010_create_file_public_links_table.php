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
        Schema::create('file_public_links', function (Blueprint $table) {
            $table->foreignId('file_id')->constrained('files', 'id')->cascadeOnDelete();
            $table->string('link_id', 64);
            $table->timestamps();

            // Indexes
            $table->primary('file_id');
            $table->unique('link_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_public_links');
    }
};
