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
        Schema::create('contrato_locacao_unidade', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidade_id');
            $table->string('nome_proprietario');
            $table->string('cpf_cnpj');
            $table->string('telefone')->nullable();
            $table->decimal('valor_locacao', 10, 2);
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->string('anexo')->nullable();
            $table->timestamps();

            $table->foreign('unidade_id')->references('id')->on('unidades');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrato_locacao_unidade');
    }
};
