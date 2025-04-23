<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContratoLocacaoUnidade extends Model
{
    use HasFactory;

    /**
     * A tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'contrato_locacao_unidade';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unidade_id',
        'nome_proprietario',
        'cpf_cnpj',
        'telefone',
        'valor_locacao',
        'data_inicio',
        'data_fim',
        'anexo',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valor_locacao' => 'decimal:2',
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    /**
     * Obtém a unidade associada a este contrato.
     */
    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    /**
     * Verifica se o contrato está ativo na data atual.
     */
    public function isAtivo(): bool
    {
        return now()->between($this->data_inicio, $this->data_fim);
    }

    /**
     * Calcula quanto tempo falta para o término do contrato.
     */
    public function getDiasRestantesAttribute(): int
    {
        return max(0, now()->diffInDays($this->data_fim, false));
    }

    /**
     * Verifica se o contrato está próximo do vencimento.
     */
    public function getProximoVencimentoAttribute(): bool
    {
        return $this->dias_restantes > 0 && $this->dias_restantes <= 60;
    }

    /**
     * Verifica se o contrato está vencido.
     */
    public function getVencidoAttribute(): bool
    {
        return now()->isAfter($this->data_fim);
    }
}