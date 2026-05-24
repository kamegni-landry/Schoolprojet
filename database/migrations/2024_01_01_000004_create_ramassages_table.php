<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramassages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('adresse');
            $table->text('description_domicile')->nullable();
            $table->enum('frequence', ['1_semaine', '2_semaine']); // 1 ou 2 fois/semaine
            $table->decimal('prix', 10, 2); // 2000 ou 3000 FCFA
            $table->string('phone_paiement'); // numéro Orange Money
            $table->enum('statut_paiement', ['en_attente', 'paye', 'echoue'])->default('en_attente');
            $table->string('reference_paiement')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->enum('statut', ['actif', 'suspendu', 'annule'])->default('actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramassages');
    }
};
