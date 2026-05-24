<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signalements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('lieu');
            $table->text('description')->nullable();
            $table->enum('type_dechet', ['Ménagers', 'Plastiques', 'Dangereux'])->default('Ménagers');
            $table->string('quartier');
            $table->string('photo')->nullable(); // chemin vers le fichier
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('statut', ['En attente', 'En cours', 'Traité'])->default('En attente');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('traite_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
