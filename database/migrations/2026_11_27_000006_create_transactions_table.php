<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->string('payment_method', 20); // cash, qris, transfer
            $table->string('payment_status', 20)->default('pending'); // pending, paid, failed, expired, cancelled, refunded
            $table->string('status', 20)->default('pending'); // pending, completed, cancelled, refunded
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'payment_status', 'status']);
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
