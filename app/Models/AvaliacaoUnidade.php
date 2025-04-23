<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvaliacaoUnidade extends Model
{
    use HasFactory;

    /**
     * A tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'avaliacoes_unidade';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unidade_id',
        'avaliador_id',
        'status',
        'nota_geral',
        'nota_estrutura',
        'nota_acessibilidade',
        'nota_conservacao',
        'observacoes',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nota_geral' => 'decimal:1',
        'nota_estrutura' => 'decimal:1',
        'nota_acessibilidade' => 'decimal:1',
        'nota_conservacao' => 'decimal:1',
    ];

    /**
     * Obtém a unidade associada a esta avaliação.
     */
    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    /**
     * Obtém o avaliador (usuário) que criou esta avaliação.
     */
    public function avaliador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'avaliador_id');
    }
}