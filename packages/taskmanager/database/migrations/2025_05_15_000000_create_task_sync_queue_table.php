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
        Schema::create('task_sync_queue', function (Blueprint $table) {
            $table->id();
            $table->string('operation');
            $table->string('entity_type');
            $table->string('entity_id');
            $table->json('data');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->integer('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'attempts', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_sync_queue');
    }
}; 