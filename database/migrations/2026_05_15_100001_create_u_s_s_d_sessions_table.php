<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('u_s_s_d_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('phone_number');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('current_step')->default('menu'); // menu, signalement, ramassage, abonnement, etc
            $table->json('data')->nullable(); // Stocke les données de la session
            $table->enum('status', ['active', 'completed', 'expired'])->default('active');
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index('phone_number');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('u_s_s_d_sessions');
    }
};
