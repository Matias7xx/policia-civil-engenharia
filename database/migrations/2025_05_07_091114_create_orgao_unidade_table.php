<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Criar a tabela pivô orgao_unidade
        Schema::create('orgao_unidade', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidade_id');
            $table->unsignedBigInteger('orgao_id');
            $table->timestamps();

            $table->foreign('unidade_id')->references('id')->on('unidades')->onDelete('cascade');
            $table->foreign('orgao_id')->references('id')->on('orgaos')->onDelete('cascade');
            $table->unique(['unidade_id', 'orgao_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         // Dropar a tabela orgao_unidade
        Schema::dropIfExists('orgao_unidade');
    }
};