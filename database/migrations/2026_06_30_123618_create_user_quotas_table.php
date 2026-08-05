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
        Schema::create('user_quotas', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('used_bytes')->default(0);
            $table->timestamp('clear_trash_at')->nullable();
            $table->timestamp('last_reconcile_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->primary('user_id');
            $table->index('last_reconcile_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_quotas');
    }
};
