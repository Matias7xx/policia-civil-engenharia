<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Unidade extends Model
{
    use HasFactory;

    /**
     * A tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'unidades';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'nome',
        'codigo',
        'tipo_estrutural',
        'srpc',
        'dspc',
        'nivel',
        'sede',
        'cidade',
        'cep',
        'rua',
        'numero',
        'bairro',
        'complemento',
        'email',
        'telefone_1',
        'telefone_2',
        'latitude',
        'longitude',
        'tipo_judicial',
        'imovel_compartilhado_unidades',
        'imovel_compartilhado_unidades_texto',
        'status',
        'imovel_compartilhado_orgao',
        'observacoes',
        'numero_medidor_agua',
        'numero_medidor_energia',
        'orgao_cedente',
        'termo_cessao',
        'prazo_cessao',
        'is_draft',
        'rejection_reason',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sede' => 'boolean',
        'imovel_compartilhado_unidades' => 'boolean',
        'imovel_compartilhado_orgao' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Obtém os órgãos com os quais o imóvel é compartilhado.
     */
    public function orgaosCompartilhados(): BelongsToMany
    {
        return $this->belongsToMany(Orgao::class, 'orgao_unidade')
                    ->withTimestamps();
    }

    /**
     * Obtém as informações de acessibilidade da unidade.
     */
    public function acessibilidade(): HasOne
    {
        return $this->hasOne(AcessibilidadeUnidade::class);
    }

    /**
     * Obtém as informações detalhadas da unidade.
     */
    public function informacoes(): HasOne
    {
        return $this->hasOne(InformacoesUnidade::class);
    }

    /**
     * Obtém o contrato de locação associado à unidade, se houver.
     */
    public function contratoLocacao(): HasOne
    {
        return $this->hasOne(ContratoLocacaoUnidade::class);
    }

    /**
     * Obtém as mídias associadas à unidade.
     */
    public function midias(): BelongsToMany
    {
        return $this->belongsToMany(Midia::class, 'midia_unidade')
                    ->withTimestamps();
    }

    /**
     * Obtém as avaliações da unidade.
     */
    public function avaliacoes(): HasMany
    {
        return $this->hasMany(AvaliacaoUnidade::class, 'unidade_id');
    }

    /**
     * Obtém a avaliação mais recente da unidade.
     */
    public function ultimaAvaliacao(): HasOne
    {
        return $this->hasOne(AvaliacaoUnidade::class, 'unidade_id')->latest();
    }

    /**
     * Verifica se a unidade possui informações de acessibilidade cadastradas.
     */
    public function hasAcessibilidade(): bool
    {
        return $this->acessibilidade()->exists();
    }

    /**
     * Verifica se a unidade possui informações detalhadas cadastradas.
     */
    public function hasInformacoes(): bool
    {
        return $this->informacoes()->exists();
    }

    /**
     * Verifica se a unidade possui contrato de locação.
     */
    public function hasContratoLocacao(): bool
    {
        return $this->contratoLocacao()->exists();
    }

    /**
     * Verifica se a unidade possui avaliações.
     */
    public function hasAvaliacoes(): bool
    {
        return $this->avaliacoes()->exists();
    }

    /**
     * Escopo para filtrar unidades por status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Escopo para filtrar unidades por tipo estrutural.
     */
    public function scopeTipoEstrutural($query, $tipo)
    {
        return $query->where('tipo_estrutural', $tipo);
    }

    /**
     * Escopo para filtrar unidades por cidade.
     */
    public function scopeCidade($query, $cidade)
    {
        return $query->where('cidade', $cidade);
    }

    /**
     * Retorna o status formatado da unidade.
     */
    public function getStatusFormatadoAttribute()
    {
        switch($this->status) {
            case 'pendente_avaliacao':
                return 'Pendente de Avaliação';
            case 'aprovada':
                return 'Aprovado';
            case 'reprovada':
                return 'Reprovado';
            case 'em_revisao':
                return 'Em Revisão';
            default:
                return 'Sem Cadastro';
        }
    }

    /**
     * Retorna a classe CSS para o status da unidade.
     */
    public function getStatusClassAttribute()
    {
        switch($this->status) {
            case 'pendente_avaliacao':
                return 'bg-yellow-100 text-yellow-800';
            case 'aprovada':
                return 'bg-green-100 text-green-800';
            case 'reprovada':
                return 'bg-red-100 text-red-800';
            case 'em_revisao':
                return 'bg-blue-100 text-blue-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    }

    /**
     * Retorna o endereço completo da unidade.
     */
    public function getEnderecoCompletoAttribute()
    {
        $endereco = [];
        
        if ($this->rua) {
            $endereco[] = $this->rua . ', ' . ($this->numero ?: 'S/N');
        }
        
        if ($this->bairro) {
            $endereco[] = $this->bairro;
        }
        
        if ($this->cidade) {
            $endereco[] = 'Cidade: ' . $this->cidade;
        }
        
        if ($this->cep) {
            $endereco[] = 'CEP: ' . $this->cep;
        }
        
        return !empty($endereco) ? implode(' - ', $endereco) : 'Endereço não informado';
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    public function usuarios()
    {
        return $this->team->users();
    }
}