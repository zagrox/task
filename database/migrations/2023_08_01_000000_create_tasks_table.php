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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in-progress', 'completed', 'blocked', 'review'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('due_date')->nullable();
            $table->float('estimated_hours')->nullable();
            $table->float('actual_hours')->nullable();
            $table->string('assigned_to')->default('user');
            $table->string('created_by');
            $table->string('feature')->nullable();
            $table->string('phase')->nullable();
            $table->string('version')->nullable();
            $table->string('github_issue_id')->nullable();
            $table->string('github_issue_number')->nullable();
            $table->string('github_issue_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
}; 