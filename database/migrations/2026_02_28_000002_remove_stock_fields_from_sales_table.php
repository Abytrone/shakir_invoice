<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'stock_id')) {
                $table->dropForeign(['stock_id']);
                $table->dropColumn('stock_id');
            }
            if (Schema::hasColumn('sales', 'quantity')) {
                $table->dropColumn('quantity');
            }
            if (Schema::hasColumn('sales', 'unit_price')) {
                $table->dropColumn('unit_price');
            }
            if (Schema::hasColumn('sales', 'discount')) {
                $table->dropColumn('discount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('stock_id')->after('client_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->after('stock_id');
            $table->decimal('unit_price', 10, 2)->after('quantity');
            $table->decimal('discount', 10, 2)->default(0)->after('unit_price');
        });
    }
};
