<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_borrowings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('book_copy_id')
                  ->constrained('book_copies')
                  ->cascadeOnDelete();

            $table->foreignId('student_id')
                  ->nullable()
                  ->constrained('students')
                  ->nullOnDelete();

            $table->foreignId('teacher_id')
                  ->nullable()
                  ->constrained('teachers')
                  ->nullOnDelete();

            $table->foreignId('issued_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->date('borrowed_date');
            $table->date('due_date');

            $table->date('returned_date')->nullable();

            $table->string('status')->default('borrowed');

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index([
                'student_id',
                'status'
            ]);

            $table->index([
                'teacher_id',
                'status'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_borrowings');
    }
};