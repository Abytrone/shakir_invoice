<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('tax_type')->default('percent');
            $table->decimal('tax_amount', 10, 2)->nullable();
            $table->string('discount_type')->default('percent');
            $table->decimal('discount_amount', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['tax_type', 'tax_amount', 'discount_type', 'discount_amount']);
        });
    }
};
