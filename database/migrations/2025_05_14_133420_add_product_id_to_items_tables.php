<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeignId('invoice_items_product_id_foreign');
            $table->dropColumn('product_id');
        });

        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropForeignId('quote_items_product_id_foreign');
            $table->dropColumn('product_id');
        });
    }
};
