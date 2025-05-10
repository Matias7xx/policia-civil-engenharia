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
        'nota_geral',
        'nota_estrutura',
        'nota_acessibilidade',
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
    
    /**
     * Obtém a letra correspondente à nota geral.
     */
    public function getNotaLetraAttribute()
    {
        $nota = $this->nota_geral;
        if (is_null($nota)) return '';
        
        if ($nota >= 9.5) return 'A+';
        if ($nota >= 9.0) return 'A';
        if ($nota >= 8.0) return 'B';
        if ($nota >= 7.0) return 'C';
        if ($nota >= 6.0) return 'D';
        if ($nota >= 5.0) return 'E';
        if ($nota >= 4.0) return 'F';
        if ($nota >= 3.0) return 'G';
        if ($nota >= 2.0) return 'H';
        if ($nota >= 1.0) return 'I';
        return 'J';
    }
    
    /**
     * Obtém a classe CSS correspondente à nota geral.
     */
    public function getNotaClassAttribute()
    {
        $nota = $this->nota_geral;
        if (is_null($nota)) return '';
        
        if ($nota >= 9.0) return 'text-green-600';
        if ($nota >= 7.0) return 'text-green-500';
        if ($nota >= 5.0) return 'text-yellow-500';
        if ($nota >= 3.0) return 'text-orange-500';
        return 'text-red-500';
    }
}