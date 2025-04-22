<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('informacoes_unidade', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidade_id');
            
            // Características da via e serviços
            $table->string('pavimentacao_rua')->nullable();
            $table->string('padrao_energia')->nullable();
            $table->string('subestacao')->nullable();
            $table->string('gerador_energia')->nullable();
            $table->string('para_raio')->nullable();
            $table->string('caixa_dagua')->nullable();
            $table->string('internet_cabeada')->nullable();
            $table->string('internet_provedor')->nullable();
            $table->string('telefone_fixo')->nullable();
            $table->string('telefone_movel')->nullable();
            
            // Características do imóvel
            $table->string('tipo_imovel')->nullable();
            $table->string('contrato_locacao_id')->nullable();
            $table->string('responsavel_locacao_cessao')->nullable();
            $table->string('escritura_publica')->nullable();
            
            // Características estruturais
            $table->decimal('qtd_pavimentos', 5, 2)->nullable();
            $table->boolean('cercado_muros')->nullable();
            $table->boolean('estacionamento_interno')->nullable();
            $table->boolean('estacionamento_externo')->nullable();
            $table->decimal('recuo_frontal', 8, 2)->nullable();
            $table->decimal('recuo_lateral', 8, 2)->nullable();
            $table->decimal('recuo_fundos', 8, 2)->nullable();
            
            // Quantitativos de espaços e instalações
            $table->integer('qtd_recepcao')->nullable();
            $table->integer('qtd_wc_publico')->nullable();
            $table->integer('qtd_gabinetes')->nullable();
            $table->integer('qtd_sala_oitiva')->nullable();
            $table->integer('qtd_wc_servidores')->nullable();
            $table->integer('qtd_alojamento_masculino')->nullable();
            $table->integer('qtd_wc_alojamento_masculino')->nullable();
            $table->integer('qtd_alojamento_feminino')->nullable();
            $table->integer('qtd_wc_alojamento_feminino')->nullable();
            $table->integer('qtd_celas_carceragem')->nullable();
            $table->integer('qtd_sala_identificacao')->nullable();
            $table->integer('qtd_cozinha')->nullable();
            $table->integer('qtd_area_servico')->nullable();
            $table->integer('qtd_deposito_apreensao')->nullable();
            
            // Suficiência de instalações
            $table->boolean('tomadas_suficientes')->nullable();
            $table->boolean('luminarias_suficientes')->nullable();
            $table->boolean('pontos_rede_suficientes')->nullable();
            $table->boolean('pontos_telefone_suficientes')->nullable();
            $table->boolean('pontos_ar_condicionado_suficientes')->nullable();
            $table->boolean('pontos_hidraulicos_suficientes')->nullable();
            $table->boolean('pontos_sanitarios_suficientes')->nullable();
            
            // Acabamentos
            $table->string('piso')->nullable();
            $table->string('parede')->nullable();
            $table->string('esquadrias')->nullable();
            $table->string('loucas_metais')->nullable();
            $table->string('forro_lage')->nullable();
            $table->string('cobertura')->nullable();
            $table->string('pintura')->nullable();
            
            // Equipamentos de segurança
            $table->string('extintor_po_quimico')->nullable();
            $table->string('extintor_co2')->nullable();
            $table->string('extintor_agua')->nullable();
            
            $table->timestamps();
            
            $table->foreign('unidade_id')->references('id')->on('unidades')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('informacoes_unidade');
    }
};