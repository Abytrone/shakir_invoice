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
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts')->default(0)->after('status');
            $table->timestamp('last_attempted_at')->nullable()->after('attempts');
            $table->string('failure_reason')->nullable()->after('last_attempted_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'last_attempted_at', 'failure_reason']);
        });
    }
};
