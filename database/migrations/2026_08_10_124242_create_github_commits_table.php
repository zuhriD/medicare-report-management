<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('github_commits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained('github_repositories')->cascadeOnDelete();
            $table->string('sha');
            $table->string('short_sha', 7);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('message')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->string('author_username')->nullable();
            $table->timestamp('authored_at')->nullable();
            $table->timestamp('committed_at')->nullable();
            $table->text('url')->nullable();
            $table->unsignedInteger('additions')->default(0);
            $table->unsignedInteger('deletions')->default(0);
            $table->unsignedInteger('files_changed')->default(0);
            $table->timestamps();

            $table->unique(['repository_id', 'sha']);
            $table->index('committed_at');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('github_commits');
    }
};
