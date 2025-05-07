<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orgao extends Model
{
    use HasFactory;

    /**
     * A tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'orgaos';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'status',
    ];

    /**
     * Obtém unidades que compartilham imóvel com este órgão.
     */
    public function unidadesCompartilhadas(): HasMany
    {
        return $this->hasMany(Unidade::class, 'imovel_compartilhado_orgao_id');
    }

    /**
     * Escopo para filtrar apenas órgãos ativos.
     */
    public function scopeAtivo($query)
    {
        return $query->where('status', 'ativo');
    }
}