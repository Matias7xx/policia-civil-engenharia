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
        Schema::table('unidades', function (Blueprint $table) {
            $table->boolean('imovel_compartilhado_unidades')->default(false)->after('tipo_judicial');
            $table->text('imovel_compartilhado_unidades_texto')->nullable()->after('imovel_compartilhado_unidades');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->dropColumn(['imovel_compartilhado_unidades', 'imovel_compartilhado_unidades_texto']);
        });
    }
};