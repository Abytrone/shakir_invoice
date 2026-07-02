<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('receipt_number')->unique();
            $table->timestamp('receipt_date');
            $table->string('received_from_name');
            $table->string('received_from_email')->nullable();
            $table->string('received_from_phone')->nullable();
            $table->text('received_from_address')->nullable();
            $table->json('items');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_rate', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
