<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->text('content');
            $table->enum('category', ['general', 'academic', 'event', 'urgent', 'holiday'])->default('general');
            $table->enum('audience_type', ['all', 'role', 'class', 'specific_users'])->default('all');
            $table->json('audience_value')->nullable();
            $table->dateTime('publish_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_pinned')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('publish_at');
            $table->index('category');
            $table->index('audience_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
