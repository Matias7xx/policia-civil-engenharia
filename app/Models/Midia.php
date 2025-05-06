<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Midia extends Model
{
    use HasFactory;

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

    protected $appends = ['url'];

    public function midiaTipo(): BelongsTo
    {
        return $this->belongsTo(MidiaTipo::class, 'midia_tipo_id');
    }

    public function unidades(): BelongsToMany
    {
        return $this->belongsToMany(Unidade::class, 'midia_unidade')
                    ->withTimestamps();
    }

    public function getUrlAttribute()
    {
        return $this->path ? Storage::url($this->path) : null;
    }

    public function getIsImagemAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

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