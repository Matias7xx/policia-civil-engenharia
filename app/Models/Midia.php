<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        'unidade_id',
        'midia_tipo_id',
        'path', // Substitui 'arquivo' para alinhar com o controlador
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
    public function midia_tipo()
{
    return $this->belongsTo(MidiaTipo::class, 'midia_tipo_id');
}

    /**
     * Obtém a unidade associada a esta mídia.
     */
    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class, 'unidade_id');
    }

    /**
     * Retorna o URL para acessar o arquivo da mídia.
     */
    public function getUrlAttribute()
    {
        return $this->path ? Storage::url($this->path) : null;
    }

    protected $appends = ['url'];

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