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
        Schema::table('auth_payments', function (Blueprint $table) {
            $table->after('auth_email', function (Blueprint $table) {
                $table->string('authorization_url')->nullable()->after('auth_email');
                $table->string('access_code')->nullable()->after('auth_email');
                $table->string('status')->default('pending');
            });

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auth_payments', function (Blueprint $table) {
            //
        });
    }
};
