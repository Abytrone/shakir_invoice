<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('recurring_invoice_number_prefix');
            $table->dropColumn('recurring_start_date');

        });
    }

};
