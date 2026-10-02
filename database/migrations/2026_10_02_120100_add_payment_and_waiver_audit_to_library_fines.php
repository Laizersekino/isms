<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('library_fines', function (Blueprint $table): void {
            $table->foreignId('paid_by')
                ->nullable()
                ->after('recorded_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('waived_by')
                ->nullable()
                ->after('paid_by')
                ->constrained('users')
                ->nullOnDelete();
            $table->date('waived_date')->nullable()->after('waived_by');
        });
    }

    public function down(): void
    {
        Schema::table('library_fines', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('paid_by');
            $table->dropConstrainedForeignId('waived_by');
            $table->dropColumn('waived_date');
        });
    }
};
