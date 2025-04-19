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
        Schema::create('github_issues', function (Blueprint $table) {
            $table->id();
            $table->integer('task_id')->unsigned();
            $table->string('repository')->comment('Repository name with owner (e.g. owner/repo)');
            $table->integer('issue_number')->unsigned()->comment('GitHub issue number');
            $table->string('issue_url');
            $table->string('issue_state')->default('open');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            
            $table->unique(['repository', 'issue_number']);
            $table->index('task_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_issues');
    }
};
