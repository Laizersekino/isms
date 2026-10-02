<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('fee_structure_id')->constrained('fee_structures');
            $table->foreignId('fee_structure_item_id')->constrained('fee_structure_items');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2);
            $table->string('status')->default('unpaid');
            $table->date('due_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['student_id', 'fee_structure_item_id'],
                'student_fees_student_item_unique'
            );
            $table->index('student_id');
            $table->index('status');
            $table->index('fee_structure_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fees');
    }
};
