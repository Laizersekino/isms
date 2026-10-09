<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->integer('installment_count')->default(3)->after('payment_plan');
            $table->decimal('installment_amount', 12, 2)->nullable()->after('installment_count');
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            $table->dropColumn(['installment_count', 'installment_amount']);
        });
    }
};