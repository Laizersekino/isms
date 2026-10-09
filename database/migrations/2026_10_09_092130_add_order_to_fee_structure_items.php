<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structure_items', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structure_items', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};