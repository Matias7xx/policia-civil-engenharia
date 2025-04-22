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
        Schema::create('midia_unidade', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidade_id');
            $table->unsignedBigInteger('midia_id');
            /* $table->text('descricao')->nullable(); */ // Descrição opcional específica para esta unidade
            $table->timestamps();
            
            $table->foreign('unidade_id')->references('id')->on('unidades')->onDelete('cascade');
            $table->foreign('midia_id')->references('id')->on('midias')->onDelete('cascade');
            
            // Garantir que não haja duplicidade
            $table->unique(['unidade_id', 'midia_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('midia_unidade');
    }
};
