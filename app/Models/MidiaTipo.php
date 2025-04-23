<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MidiaTipo extends Model
{
    use HasFactory;

    /**
     * A tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'midia_tipos';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'descricao',
        'ativo',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ativo' => 'boolean',
    ];

    /**
     * Obtém as mídias deste tipo.
     */
    public function midias(): HasMany
    {
        return $this->hasMany(Midia::class, 'midia_tipo_id');
    }

    /**
     * Escopo para filtrar apenas tipos de mídia ativos.
     */
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }
}