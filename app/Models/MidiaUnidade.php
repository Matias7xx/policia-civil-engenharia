<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class MidiaUnidade extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    /**
     * A tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'midia_unidade';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unidade_id',
        'midia_id',
        'nao_possui_ambiente',
        'observacoes',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'nao_possui_ambiente' => 'boolean',
    ];

    // Configuração de auditoria
    protected $auditInclude = [
        'nao_possui_ambiente',
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
            'midia_unidade',
            'unidade:' . $this->unidade_id,
        ];

        if ($this->unidade) {
            $tags[] = 'team:' . $this->unidade->team_id;
        }

        if ($this->midia && $this->midia->midiaTipo) {
            $tags[] = 'tipo_midia:' . strtolower($this->midia->midiaTipo->nome);
        }

        if ($this->nao_possui_ambiente) {
            $tags[] = 'nao_possui_ambiente';
        }

        return $tags;
    }

    /**
     * Obtém a unidade associada a esta mídia.
     */
    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    /**
     * Obtém a mídia associada a esta unidade.
     */
    public function midia(): BelongsTo
    {
        return $this->belongsTo(Midia::class);
    }

    /**
     *  buscar registros que indicam "não possui ambiente"
     */
    public function scopeNaoPossuiAmbiente($query)
    {
        return $query->where('nao_possui_ambiente', true);
    }

    /**
     *  buscar registros que possuem mídia real
     */
    public function scopeComMidia($query)
    {
        return $query->where('nao_possui_ambiente', false);
    }
}