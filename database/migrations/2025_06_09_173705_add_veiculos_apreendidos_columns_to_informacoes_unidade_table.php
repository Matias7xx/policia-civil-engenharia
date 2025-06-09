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
        Schema::table('informacoes_unidade', function (Blueprint $table) {
            $table->boolean('tem_espaco_veiculos_apreendidos')->default(false)->after('placa_incendio');
            $table->integer('qtd_max_veiculos_automovel')->nullable()->after('tem_espaco_veiculos_apreendidos');
            $table->string('seguranca_local_veiculos')->nullable()->after('qtd_max_veiculos_automovel');
            $table->boolean('historico_invasao_veiculo')->default(false)->after('seguranca_local_veiculos');
            $table->text('observacoes_veiculos_apreendidos')->nullable()->after('historico_invasao_veiculo');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('informacoes_unidade', function (Blueprint $table) {
            $table->dropColumn([
                'tem_espaco_veiculos_apreendidos',
                'qtd_max_veiculos_automovel',
                'seguranca_local_veiculos',
                'historico_invasao_veiculo',
            ]);
        });
    }
};