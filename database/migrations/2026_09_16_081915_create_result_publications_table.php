<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_publications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')
                  ->constrained('exams')
                  ->cascadeOnDelete();

            $table->foreignId('class_id')
                  ->constrained('classes')
                  ->cascadeOnDelete();

            $table->foreignId('published_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamp('published_at')->nullable();

            $table->string('status')->default('draft');

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique([
                'exam_id',
                'class_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_publications');
    }
};