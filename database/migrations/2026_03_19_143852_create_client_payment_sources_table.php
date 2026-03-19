<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_payment_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method');
            $table->string('label');
            $table->string('source_number');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['client_id', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_payment_sources');
    }
};
