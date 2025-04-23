<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Midia extends Model
{
    use HasFactory;

    /**
     * A tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'midias';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'midia_tipo_id',
        'arquivo',
        'mime_type',
        'tamanho',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tamanho' => 'integer',
    ];

    /**
     * Obtém o tipo da mídia.
     */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(MidiaTipo::class, 'midia_tipo_id');
    }

    /**
     * Obtém as unidades associadas a esta mídia.
     */
    public function unidades(): BelongsToMany
    {
        return $this->belongsToMany(Unidade::class, 'midia_unidade')
                    ->withTimestamps();
    }

    /**
     * Retorna o URL para acessar o arquivo da mídia.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->arquivo);
    }

    /**
     * Verifica se esta mídia é uma imagem.
     */
    public function getIsImagemAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Verifica se esta mídia é um documento PDF.
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Formata o tamanho do arquivo para exibição.
     */
    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho;
        
        if ($bytes < 1024) {
            return $bytes . ' bytes';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }
}