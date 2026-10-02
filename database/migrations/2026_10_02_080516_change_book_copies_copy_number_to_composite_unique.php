<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_copies', function (Blueprint $table): void {
            $table->dropUnique('book_copies_copy_number_unique');
            $table->unique(['book_id', 'copy_number']);
        });
    }

    public function down(): void
    {
        Schema::table('book_copies', function (Blueprint $table): void {
            $table->dropUnique('book_copies_book_id_copy_number_unique');
            $table->unique('copy_number');
        });
    }
};
