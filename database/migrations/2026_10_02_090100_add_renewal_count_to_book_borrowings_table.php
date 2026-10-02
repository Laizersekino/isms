<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_borrowings', function (Blueprint $table): void {
            $table->unsignedInteger('renewal_count')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('book_borrowings', function (Blueprint $table): void {
            $table->dropColumn('renewal_count');
        });
    }
};
