<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_fee_id')->constrained('student_fees');
            $table->foreignId('student_id')->constrained('students');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', [
                'cash',
                'bank_transfer',
                'mobile_money',
                'cheque',
                'other',
            ]);
            $table->string('reference_number', 100)->nullable();
            $table->string('receipt_number')->unique();
            $table->date('paid_date');
            $table->foreignId('recorded_by')->constrained('users');
            $table->enum('status', ['completed', 'reversed'])->default('completed');
            $table->dateTime('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users');
            $table->text('reversal_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('student_fee_id');
            $table->index('student_id');
            $table->index('status');
            $table->index('paid_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
