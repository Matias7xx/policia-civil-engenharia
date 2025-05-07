<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    public function unidadesCompartilhadas(): BelongsToMany
    {
        return $this->belongsToMany(Unidade::class, 'orgao_unidade')
                    ->withTimestamps();
    }

    /**
     * Escopo para filtrar apenas órgãos ativos.
     */
    public function scopeAtivo($query)
    {
        return $query->where('status', 'ativo');
    }
}