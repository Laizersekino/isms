<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structure_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fee_structure_id')
                ->constrained('fee_structures')
                ->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 12, 2);
            $table->boolean('is_mandatory')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_items');
    }
};
