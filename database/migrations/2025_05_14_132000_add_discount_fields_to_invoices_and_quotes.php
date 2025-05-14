<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('discount_rate', 5, 2)->default(0)->after('tax_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_rate');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('discount_rate', 5, 2)->default(0)->after('tax_amount');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_rate');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_rate', 'discount_amount']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['discount_rate', 'discount_amount']);
        });
    }
};