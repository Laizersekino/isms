<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('isbn')->nullable()->unique();

            $table->string('author')->nullable();
            $table->string('publisher')->nullable();

            $table->string('category')->nullable();

            $table->string('edition')->nullable();
            $table->integer('publication_year')->nullable();

            $table->text('description')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};