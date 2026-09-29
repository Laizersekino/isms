<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')
                  ->constrained('exams')
                  ->cascadeOnDelete();

            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();

            $table->foreignId('class_id')
                  ->constrained('classes')
                  ->cascadeOnDelete();

            $table->decimal('max_marks', 5, 2)->default(100);

            $table->timestamps();

            $table->unique([
                'exam_id',
                'subject_id',
                'class_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_subjects');
    }
};