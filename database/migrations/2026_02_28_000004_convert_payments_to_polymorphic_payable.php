<?php

use App\Models\Invoice;
use App\Models\Sale;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payable_type')->nullable()->after('type');
            $table->unsignedBigInteger('payable_id')->nullable()->after('payable_type');
            $table->index(['payable_type', 'payable_id']);
        });

        // Migrate existing data
        DB::table('payments')->whereNotNull('invoice_id')->update([
            'payable_type' => Invoice::class,
            'payable_id' => DB::raw('invoice_id'),
        ]);
        DB::table('payments')->whereNotNull('sale_id')->whereNull('payable_type')->update([
            'payable_type' => Sale::class,
            'payable_id' => DB::raw('sale_id'),
        ]);

//        Schema::table('payments', function (Blueprint $table) {
//            $table->dropForeign(['invoice_id']);
//        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['invoice_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('id');
            $table->foreignId('sale_id')->nullable()->after('invoice_id')->constrained()->nullOnDelete();
        });

        DB::table('payments')->where('payable_type', Invoice::class)->update([
            'invoice_id' => DB::raw('payable_id'),
        ]);
        DB::table('payments')->where('payable_type', Sale::class)->update([
            'sale_id' => DB::raw('payable_id'),
        ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payable_type', 'payable_id']);
            $table->dropColumn(['payable_type', 'payable_id']);
        });
    }
};
