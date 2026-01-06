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
        Schema::table('clients', function (Blueprint $table) {
            $table->after('email', function(Blueprint $table){
                $table->string('auth_email')->nullable();
                $table->string('auth_res')->nullable();
            });
        });
    }


};
