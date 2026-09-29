<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code');

            $table->decimal('min_percentage', 5, 2);
            $table->decimal('max_percentage', 5, 2);

            $table->text('description')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();

            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};