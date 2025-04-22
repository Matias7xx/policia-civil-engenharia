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
        Schema::create('acessibilidade_unidade', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidade_id');
            $table->boolean('rampa_acesso')->default(false);
            $table->boolean('corrimao')->default(false);
            $table->boolean('piso_tatil')->default(false);
            $table->boolean('banheiro_adaptado')->default(false);
            $table->boolean('elevador')->default(false);
            $table->boolean('sinalizacao_braile')->default(false);
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->foreign('unidade_id')->references('id')->on('unidades')
                ->onDelete('cascade'); /* onDelete('cascade') garante que, se uma unidade for excluída, o registro correspondente de acessibilidade também será removido, mantendo a integridade do banco de dados. */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acessibilidade_unidade');
    }
};
