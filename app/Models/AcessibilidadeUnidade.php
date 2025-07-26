<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class AcessibilidadeUnidade extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    /**
     * A tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'acessibilidade_unidade';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unidade_id',
        'rampa_acesso',
        'corrimao',
        'piso_tatil',
        'banheiro_adaptado',
        'elevador',
        'sinalizacao_braile',
        'observacoes',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rampa_acesso' => 'boolean',
        'corrimao' => 'boolean',
        'piso_tatil' => 'boolean',
        'banheiro_adaptado' => 'boolean',
        'elevador' => 'boolean',
        'sinalizacao_braile' => 'boolean',
    ];

    // Configuração de auditoria
    protected $auditInclude = [
        'rampa_acesso',
        'corrimao',
        'piso_tatil',
        'banheiro_adaptado',
        'elevador',
        'sinalizacao_braile',
        'observacoes',
    ];

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    public function getAuditableType(): string
    {
        return static::class;
    }

    public function generateTags(): array
    {
        $tags = [
            'acessibilidade',
            'unidade:' . $this->unidade_id,
        ];

        if ($this->unidade) {
            $tags[] = 'team:' . $this->unidade->team_id;
            if ($this->unidade->cidade) {
                $tags[] = 'cidade:' . strtolower($this->unidade->cidade);
            }
        }

        return $tags;
    }

    /**
     * Obtém a unidade associada a este registro de acessibilidade.
     */
    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }
}