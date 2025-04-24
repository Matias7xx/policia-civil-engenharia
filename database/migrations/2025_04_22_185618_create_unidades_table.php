<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->string('nome');
            $table->string('codigo')->nullable();
            $table->string('tipo_estrutural')->nullable();
            $table->string('srpc')->nullable()->comment('Superintendência Regional de Polícia Civil');
            $table->string('dspc')->nullable()->comment('Delegacia Seccional de Polícia Civil');
            $table->string('nivel')->nullable();
            $table->boolean('sede')->default(false);
            $table->unsignedBigInteger('cidade_id')->nullable();
            $table->string('cep')->nullable();
            $table->string('rua')->nullable();
            $table->string('numero')->nullable();
            $table->string('bairro')->nullable();
            $table->string('complemento')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone_1')->nullable();
            $table->string('telefone_2')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('tipo_judicial')->nullable()->comment('próprio, locado, cedido (orgao cedente, termo de cessão e prazo de cessão)');
            $table->string('status')->nullable();
            $table->boolean('imovel_compartilhado_orgao')->default(false);
            $table->unsignedBigInteger('imovel_compartilhado_orgao_id')->nullable();
            $table->string('observacoes')->nullable();
            $table->string('numero_medidor_agua')->nullable();
            $table->string('numero_medidor_energia')->nullable();
            $table->timestamps();
            
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('imovel_compartilhado_orgao_id')->references('id')->on('orgaos');
        });
    }

    public function down()
    {
        Schema::dropIfExists('unidades');
    }
};