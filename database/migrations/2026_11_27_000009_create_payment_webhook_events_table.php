<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->string('idempotency_key', 191)->unique();
            $table->string('order_id', 120)->nullable()->index();
            $table->string('event_type', 60)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->json('payload');
            $table->string('status', 20)->default('received'); // received, processed, ignored, failed
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
