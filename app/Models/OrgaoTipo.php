<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgaoTipo extends Model
{
    use HasFactory;

    /**
     * A tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'orgao_tipos';

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
     * Obtém os órgãos deste tipo.
     */
    public function orgaos(): HasMany
    {
        return $this->hasMany(Orgao::class, 'orgao_tipos_id');
    }

    /**
     * Escopo para filtrar apenas tipos de órgão ativos.
     */
    public function scopeAtivo($query)
    {
        return $query->where('status', 'ativo');
    }
}