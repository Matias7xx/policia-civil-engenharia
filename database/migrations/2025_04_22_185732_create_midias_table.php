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
        Schema::create('midias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('midia_tipo_id');
            /* $table->unsignedBigInteger('unidade_id'); */
            $table->string('path');    // Caminho para o arquivo
            $table->string('mime_type')->nullable(); /* Armazena o tipo MIME do arquivo (ex: "image/jpeg", "image/png") para validar que apenas tipos de arquivo permitidos sejam enviados */
            $table->integer('tamanho')->nullable(); // Tamanho em bytes para implementar limites de upload
            $table->timestamps();
            
            $table->foreign('midia_tipo_id')->references('id')->on('midia_tipos');
            /* $table->foreign('unidade_id')->references('id')->on('unidades')->onDelete('cascade'); */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('midias');
    }
};
