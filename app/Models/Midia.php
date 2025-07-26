<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use App\Helpers\StorageHelper;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Midia extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $table = 'midias';

    protected $fillable = [
        'midia_tipo_id',
        'path',
        'mime_type',
        'tamanho',
    ];

    protected $casts = [
        'tamanho' => 'integer',
    ];

    protected $appends = [
        'url', 
        'is_imagem', 
        'tamanho_formatado',
        'download_url'
    ];

    // Configuração de auditoria
    protected $auditInclude = [
        'midia_tipo_id',
        'path',
        'mime_type',
        'tamanho',
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
            'midia',
        ];

        if ($this->midiaTipo) {
            $tags[] = 'tipo_midia:' . strtolower($this->midiaTipo->nome);
        }

        if ($this->is_imagem) {
            $tags[] = 'imagem';
        }

        // Adicionar tags das unidades relacionadas
        foreach ($this->unidades as $unidade) {
            $tags[] = 'unidade:' . $unidade->id;
            $tags[] = 'team:' . $unidade->team_id;
        }

        return array_unique($tags);
    }

    public function midiaTipo(): BelongsTo
    {
        return $this->belongsTo(MidiaTipo::class, 'midia_tipo_id');
    }

    public function unidades(): BelongsToMany
    {
        return $this->belongsToMany(Unidade::class, 'midia_unidade', 'midia_id', 'unidade_id')
                    ->withPivot('nao_possui_ambiente', 'observacoes')
                    ->withTimestamps();
    }

    /**
     * Gerar URL para visualização da mídia
     */
    public function getUrlAttribute()
    {
        // Se for um registro de "não possui ambiente", não tem URL
        if ($this->path === 'nao_possui_ambiente') {
            return null;
        }

        //rota que busca no MinIO
        return route('midias.view', $this->id);
    }

    /**
     * URL para download da mídia
     */
    public function getDownloadUrlAttribute()
    {
        if ($this->path === 'nao_possui_ambiente') {
            return null;
        }

        return route('midias.download', $this->id);
    }

    /**
     * Verificar se é uma imagem
     */
    public function getIsImagemAttribute(): bool
    {
        if ($this->path === 'nao_possui_ambiente') {
            return false;
        }

        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Formatar tamanho do arquivo
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

    /**
     * Verificar se o arquivo existe no MinIO
     */
    public function arquivoExiste()
    {
        if ($this->path === 'nao_possui_ambiente') {
            return false;
        }

        // Verificar no MinIO
        return StorageHelper::arquivoExiste($this->path);
    }

    /**
     * Obter conteúdo do arquivo
     */
    public function obterConteudo()
    {
        if ($this->path === 'nao_possui_ambiente') {
            return null;
        }

        // Buscar no MinIO
        return StorageHelper::obterArquivo($this->path);
    }

    /**
     * Scope para filtrar apenas mídias reais (não "não possui ambiente")
     */
    public function scopeReais($query)
    {
        return $query->where('path', '!=', 'nao_possui_ambiente');
    }

    /**
     * Scope para filtrar apenas registros de "não possui ambiente"
     */
    public function scopeNaoPossui($query)
    {
        return $query->where('path', '=', 'nao_possui_ambiente');
    }

    /**
     * Scope para filtrar apenas imagens
     */
    public function scopeImagens($query)
    {
        return $query->where('mime_type', 'LIKE', 'image/%')
                     ->where('path', '!=', 'nao_possui_ambiente');
    }

    /**
     * Remover arquivo físico ao deletar o registro
     */
    protected static function booted()
    {
        static::deleting(function ($midia) {
            if ($midia->path !== 'nao_possui_ambiente') {  
                // Remover do MinIO
                StorageHelper::removerArquivo($midia->path);
            }
        });
    }
}