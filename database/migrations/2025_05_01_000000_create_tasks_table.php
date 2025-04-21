<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('assignee', ['user', 'ai'])->default('user');
            $table->enum('status', ['pending', 'in-progress', 'completed', 'blocked', 'review'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('due_date')->nullable();
            $table->string('related_feature')->nullable();
            $table->string('related_phase')->nullable();
            $table->integer('progress')->default(0);
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->string('version')->nullable();
            $table->text('notes')->nullable(); // JSON array of notes
            $table->text('tags')->nullable(); // JSON array of tags
            $table->text('dependencies')->nullable(); // JSON array of dependencies
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}; 