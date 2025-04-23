<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformacoesUnidade extends Model
{
    use HasFactory;

    /**
     * A tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'informacoes_unidade';

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unidade_id',
        'pavimentacao_rua',
        'padrao_energia',
        'subestacao',
        'gerador_energia',
        'para_raio',
        'caixa_dagua',
        'internet_cabeada',
        'internet_provedor',
        'telefone_fixo',
        'telefone_movel',
        'tipo_imovel',
        'contrato_locacao_id',
        'responsavel_locacao_cessao',
        'escritura_publica',
        'qtd_pavimentos',
        'cercado_muros',
        'estacionamento_interno',
        'estacionamento_externo',
        'recuo_frontal',
        'recuo_lateral',
        'recuo_fundos',
        'qtd_recepcao',
        'qtd_wc_publico',
        'qtd_gabinetes',
        'qtd_sala_oitiva',
        'qtd_wc_servidores',
        'qtd_alojamento_masculino',
        'qtd_wc_alojamento_masculino',
        'qtd_alojamento_feminino',
        'qtd_wc_alojamento_feminino',
        'qtd_celas_carceragem',
        'qtd_sala_identificacao',
        'qtd_cozinha',
        'qtd_area_servico',
        'qtd_deposito_apreensao',
        'tomadas_suficientes',
        'luminarias_suficientes',
        'pontos_rede_suficientes',
        'pontos_telefone_suficientes',
        'pontos_ar_condicionado_suficientes',
        'pontos_hidraulicos_suficientes',
        'pontos_sanitarios_suficientes',
        'piso',
        'parede',
        'esquadrias',
        'loucas_metais',
        'forro_lage',
        'cobertura',
        'pintura',
        'extintor_po_quimico',
        'extintor_co2',
        'extintor_agua',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'qtd_pavimentos' => 'decimal:2',
        'cercado_muros' => 'boolean',
        'estacionamento_interno' => 'boolean',
        'estacionamento_externo' => 'boolean',
        'recuo_frontal' => 'decimal:2',
        'recuo_lateral' => 'decimal:2',
        'recuo_fundos' => 'decimal:2',
        'qtd_recepcao' => 'integer',
        'qtd_wc_publico' => 'integer',
        'qtd_gabinetes' => 'integer',
        'qtd_sala_oitiva' => 'integer',
        'qtd_wc_servidores' => 'integer',
        'qtd_alojamento_masculino' => 'integer',
        'qtd_wc_alojamento_masculino' => 'integer',
        'qtd_alojamento_feminino' => 'integer',
        'qtd_wc_alojamento_feminino' => 'integer',
        'qtd_celas_carceragem' => 'integer',
        'qtd_sala_identificacao' => 'integer',
        'qtd_cozinha' => 'integer',
        'qtd_area_servico' => 'integer',
        'qtd_deposito_apreensao' => 'integer',
        'tomadas_suficientes' => 'boolean',
        'luminarias_suficientes' => 'boolean',
        'pontos_rede_suficientes' => 'boolean',
        'pontos_telefone_suficientes' => 'boolean',
        'pontos_ar_condicionado_suficientes' => 'boolean',
        'pontos_hidraulicos_suficientes' => 'boolean',
        'pontos_sanitarios_suficientes' => 'boolean',
    ];

    /**
     * Obtém a unidade associada a estas informações.
     */
    public function unidade(): BelongsTo
    {
        return $this->belongsTo(Unidade::class);
    }

    /**
     * Obtém o contrato de locação referenciado, se houver.
     */
    public function contratoLocacao(): BelongsTo
    {
        return $this->belongsTo(ContratoLocacaoUnidade::class, 'contrato_locacao_id');
    }
}