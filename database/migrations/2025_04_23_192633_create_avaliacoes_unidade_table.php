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
        Schema::create('avaliacoes_unidade', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidade_id');
            $table->unsignedBigInteger('avaliador_id');
            $table->decimal('nota_geral', 3, 1)->nullable();
            $table->decimal('nota_estrutura', 3, 1)->nullable();
            $table->decimal('nota_acessibilidade', 3, 1)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            
            $table->foreign('unidade_id')->references('id')->on('unidades')
                ->onDelete('cascade');
            $table->foreign('avaliador_id')->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avaliacoes_unidade');
    }
};