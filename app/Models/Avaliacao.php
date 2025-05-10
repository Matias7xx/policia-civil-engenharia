<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avaliacao extends Model
{
    use HasFactory;

    /**
     * A tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'avaliacoes';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unidade_id',
        'user_id',
        'nota_estrutura',
        'nota_acessibilidade',
        'nota_geral',
        'observacoes',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nota_estrutura' => 'decimal:1',
        'nota_acessibilidade' => 'decimal:1',
        'nota_geral' => 'decimal:1',
    ];

    /**
     * Obtém a unidade associada a esta avaliação.
     */
    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    /**
     * Obtém o usuário que realizou a avaliação.
     */
    public function avaliador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Atualiza a nota geral antes de salvar.
     */
    protected static function booted()
    {
        static::creating(function ($avaliacao) {
            if (is_null($avaliacao->nota_geral)) {
                $avaliacao->nota_geral = $avaliacao->calcularNotaGeral();
            }
        });

        static::updating(function ($avaliacao) {
            $avaliacao->nota_geral = $avaliacao->calcularNotaGeral();
        });
    }

    /**
     * Retorna a classificação baseada na nota geral.
     */
    public function getClassificacaoAttribute(): string
    {
        $nota = $this->nota_geral;

        if ($nota >= 9.0) {
            return 'Excelente';
        } elseif ($nota >= 7.0) {
            return 'Bom';
        } elseif ($nota >= 5.0) {
            return 'Regular';
        } elseif ($nota >= 3.0) {
            return 'Ruim';
        } else {
            return 'Crítico';
        }
    }

    /**
     * Retorna a classe CSS para a nota geral.
     */
    public function getNotaClassAttribute(): string
    {
        $nota = $this->nota_geral;

        if ($nota >= 9.0) {
            return 'bg-green-100 text-green-800';
        } elseif ($nota >= 7.0) {
            return 'bg-green-50 text-green-600';
        } elseif ($nota >= 5.0) {
            return 'bg-yellow-100 text-yellow-800';
        } elseif ($nota >= 3.0) {
            return 'bg-orange-100 text-orange-800';
        } else {
            return 'bg-red-100 text-red-800';
        }
    }
}