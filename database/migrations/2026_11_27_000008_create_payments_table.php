<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('method', 20);
            $table->string('provider', 30)->nullable(); // manual, midtrans, xendit
            $table->string('provider_reference', 120)->nullable()->index();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('cash_received')->nullable();
            $table->unsignedBigInteger('change_amount')->nullable();
            $table->text('qris_string')->nullable();
            $table->text('qris_url')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
