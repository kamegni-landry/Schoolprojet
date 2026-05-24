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
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['payment', 'refund', 'withdrawal']);
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('XAF');
            $table->string('reference')->unique(); // SIG-2026-0012, TXN-2026-0001, etc
            $table->string('phone_number');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->enum('payment_method', ['mtn', 'orange', 'card', 'bank'])->nullable();
            $table->string('provider')->nullable(); // africa-talking, stripe, etc
            $table->json('transaction_data')->nullable(); // Réponse du provider
            $table->text('error_message')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('reference');
            $table->index('phone_number');
            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
