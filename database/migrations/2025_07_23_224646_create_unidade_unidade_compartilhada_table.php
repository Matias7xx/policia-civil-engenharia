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
        Schema::create('unidade_unidade_compartilhada', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidade_id')
                  ->constrained('unidades')
                  ->onDelete('cascade');
            $table->foreignId('unidade_compartilhada_id')
                  ->constrained('unidades')
                  ->onDelete('cascade');
            $table->timestamps();

            // Garantir que não há duplicatas e que uma unidade não se relacione consigo mesma
            $table->unique(['unidade_id', 'unidade_compartilhada_id'], 'unidade_compartilhada_unique');
            $table->index(['unidade_id']);
            $table->index(['unidade_compartilhada_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidade_unidade_compartilhada');
    }
};