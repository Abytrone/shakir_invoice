<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique()->after('sale_uuid');
        });

        // Backfill existing sales with SAL000001, SAL000002, ...
        $sales = DB::table('sales')->orderBy('id')->get();
        foreach ($sales as $index => $sale) {
            $num = $index + 1;
            DB::table('sales')->where('id', $sale->id)->update([
                'reference' => 'SAL' . str_pad((string) $num, 6, '0', STR_PAD_LEFT),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
};
