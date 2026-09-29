<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_fines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('book_borrowing_id')
                  ->constrained('book_borrowings')
                  ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);

            $table->string('reason');

            $table->string('status')->default('unpaid');

            $table->date('issued_date');

            $table->date('paid_date')->nullable();

            $table->foreignId('recorded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_fines');
    }
};