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
        'nota_instalacoes',
        'nota_conservacao',
        'nota_geral',
        'observacoes',
        'status',
        'recomendacoes',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nota_estrutura' => 'decimal:1',
        'nota_acessibilidade' => 'decimal:1',
        'nota_instalacoes' => 'decimal:1',
        'nota_conservacao' => 'decimal:1',
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
     * Calcula a nota geral a partir das notas específicas.
     */
    public function calcularNotaGeral()
    {
        // Pesos para cada categoria
        $pesos = [
            'nota_estrutura' => 0.3,
            'nota_acessibilidade' => 0.2,
            'nota_instalacoes' => 0.3,
            'nota_conservacao' => 0.2,
        ];

        $somaNotas = 0;
        $somaPesos = 0;

        foreach ($pesos as $campo => $peso) {
            if (!is_null($this->$campo)) {
                $somaNotas += $this->$campo * $peso;
                $somaPesos += $peso;
            }
        }

        if ($somaPesos > 0) {
            return round($somaNotas / $somaPesos, 1);
        }

        return null;
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