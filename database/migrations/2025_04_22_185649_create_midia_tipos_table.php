<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('midia_tipos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');       // Ex: frente, lateral, banheiro
            $table->string('descricao')->nullable();
            $table->boolean('ativo')->default(true); /* Controla quais tipos de mídia estão disponíveis no sistema sem precisar excluí-los do banco de dados. */
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('midia_tipos');
    }
};
