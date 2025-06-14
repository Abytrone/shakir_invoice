<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('amount_paid', 10)->default(0);
            $table->decimal('tax_rate', 10)->default(0);
            $table->decimal('discount_rate', 10)->default(0);
            $table->string('status')->default('draft'); // draft, sent, paid, overdue, cancelled
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            // Recurring invoice fields
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_frequency')->nullable(); // daily, weekly, monthly, yearly
            $table->date('next_recurring_date')->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->boolean('has_next')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
