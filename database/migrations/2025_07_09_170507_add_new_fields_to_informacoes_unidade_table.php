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
            $table->string('porta_principal_abre_fora')->nullable()->after('placa_incendio');
            $table->string('possui_luminarias_emergencia')->nullable()->after('porta_principal_abre_fora');
            $table->string('escada_possui_corrimao')->nullable()->after('possui_luminarias_emergencia');
            $table->string('demarcacao_piso_extintor')->nullable()->after('escada_possui_corrimao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('informacoes_unidade', function (Blueprint $table) {
            $table->dropColumn([
                'porta_principal_abre_fora',
                'possui_luminarias_emergencia',
                'escada_possui_corrimao',
                'demarcacao_piso_extintor'
            ]);
        });
    }
};