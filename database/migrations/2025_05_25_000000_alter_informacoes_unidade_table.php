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
        Schema::table('informacoes_unidade', function (Blueprint $table) {
            $table->decimal('area_aproximada_unidade', 10, 2)->nullable()->after('escritura_publica');
            $table->string('ponto_energia_agua')->nullable()->after('recuo_fundos');
            $table->decimal('area_xadrez_masculino', 10, 2)->nullable()->after('qtd_xadrez_masculino');
            $table->integer('qtd_xadrez_feminino')->nullable()->after('area_xadrez_masculino');
            $table->decimal('area_xadrez_feminino', 10, 2)->nullable()->after('qtd_xadrez_feminino');
        });

        // Renomear a coluna qtd_celas_carceragem para qtd_xadrez_masculino
        // DB::statement para garantir que os dados sejam preservados
        DB::statement('ALTER TABLE informacoes_unidade RENAME COLUMN qtd_celas_carceragem TO qtd_xadrez_masculino');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('informacoes_unidade', function (Blueprint $table) {
            $table->dropColumn('area_aproximada_unidade');
            $table->dropColumn('ponto_energia_agua');
            $table->dropColumn('area_xadrez_masculino');
            $table->dropColumn('qtd_xadrez_feminino');
            $table->dropColumn('area_xadrez_feminino');
        });

        // Reverter o renomeamento da coluna
        DB::statement('ALTER TABLE informacoes_unidade RENAME COLUMN qtd_xadrez_masculino TO qtd_celas_carceragem');
    }
};