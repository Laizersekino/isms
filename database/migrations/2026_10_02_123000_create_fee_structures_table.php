<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->foreignId('term_id')->constrained('terms');
            $table->foreignId('class_id')->constrained('classes');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['academic_year_id', 'term_id', 'class_id'],
                'fee_structures_year_term_class_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
