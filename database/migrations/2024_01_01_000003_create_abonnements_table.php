<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('plan', ['basique', 'standard', 'premium']);
            $table->decimal('prix', 10, 2)->default(0);
            $table->enum('statut', ['actif', 'expire', 'annule'])->default('actif');
            $table->timestamp('date_debut');
            $table->timestamp('date_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements');
    }
};
