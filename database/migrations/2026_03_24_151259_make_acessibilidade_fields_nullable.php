<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Torna os campos de acessibilidade nullable para suportar null = "Não Possui".
 * Antes: boolean default(false) — não permitia distinguir "Não" de "Não informado"
 * Depois: boolean nullable — null = "Não Possui" declarado | true = Sim | false = Não
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acessibilidade_unidade', function (Blueprint $table) {
            $table->boolean('rampa_acesso')->nullable()->default(null)->change();
            $table->boolean('corrimao')->nullable()->default(null)->change();
            $table->boolean('piso_tatil')->nullable()->default(null)->change();
            $table->boolean('banheiro_adaptado')->nullable()->default(null)->change();
            $table->boolean('elevador')->nullable()->default(null)->change();
            $table->boolean('sinalizacao_braile')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('acessibilidade_unidade', function (Blueprint $table) {
            $table->boolean('rampa_acesso')->nullable(false)->default(false)->change();
            $table->boolean('corrimao')->nullable(false)->default(false)->change();
            $table->boolean('piso_tatil')->nullable(false)->default(false)->change();
            $table->boolean('banheiro_adaptado')->nullable(false)->default(false)->change();
            $table->boolean('elevador')->nullable(false)->default(false)->change();
            $table->boolean('sinalizacao_braile')->nullable(false)->default(false)->change();
        });
    }
};