<?php

namespace App\Http\Controllers;

use App\Models\InformacoesUnidade;
use App\Models\Team;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InformacoesUnidadeController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Buscar a unidade pelo team_id
        $unidade = Unidade::where('team_id', $request->team_id)->firstOrFail();
        $team = Team::findOrFail($request->team_id);
        
        // Verificar permissão de atualização
        Gate::authorize('update', $team);
        
        // Validar os dados
        $validatedData = $request->validate([
            'unidade_id' => 'required|exists:unidades,id',
            
            // Características da via e serviços
            'pavimentacao_rua' => 'nullable|string|max:255',
            'padrao_energia' => 'nullable|string|max:255',
            'subestacao' => 'nullable|string|max:255',
            'gerador_energia' => 'nullable|string|max:255',
            'para_raio' => 'nullable|string|max:255',
            'caixa_dagua' => 'nullable|string|max:255',
            'internet_cabeada' => 'nullable|string|max:255',
            'internet_provedor' => 'nullable|string|max:255',
            'telefone_fixo' => 'nullable|string|max:20',
            'telefone_movel' => 'nullable|string|max:20',
            
            // Características do imóvel
            'tipo_imovel' => 'nullable|string|max:255',
            'contrato_locacao_id' => 'nullable|string|max:255',
            'responsavel_locacao_cessao' => 'nullable|string|max:255',
            'escritura_publica' => 'nullable|string|max:255',
            
            // Características estruturais
            'qtd_pavimentos' => 'nullable|numeric',
            'cercado_muros' => 'nullable|boolean',
            'estacionamento_interno' => 'nullable|boolean',
            'estacionamento_externo' => 'nullable|boolean',
            'recuo_frontal' => 'nullable|numeric',
            'recuo_lateral' => 'nullable|numeric',
            'recuo_fundos' => 'nullable|numeric',
            
            // Quantitativos de espaços e instalações
            'qtd_recepcao' => 'nullable|integer',
            'qtd_wc_publico' => 'nullable|integer',
            'qtd_gabinetes' => 'nullable|integer',
            'qtd_sala_oitiva' => 'nullable|integer',
            'qtd_wc_servidores' => 'nullable|integer',
            'qtd_alojamento_masculino' => 'nullable|integer',
            'qtd_wc_alojamento_masculino' => 'nullable|integer',
            'qtd_alojamento_feminino' => 'nullable|integer',
            'qtd_wc_alojamento_feminino' => 'nullable|integer',
            'qtd_xadrez_masculino' => 'nullable|integer',
            'area_xadrez_masculino' => 'nullable|numeric',
            'qtd_xadrez_feminino' => 'nullable|integer',
            'area_xadrez_feminino' => 'nullable|numeric',
            'qtd_sala_identificacao' => 'nullable|integer',
            'qtd_cozinha' => 'nullable|integer',
            'qtd_area_servico' => 'nullable|integer',
            'qtd_deposito_apreensao' => 'nullable|integer',
            'area_aproximada_unidade' => 'nullable|numeric',
            'area_aproximada_terreno' => 'nullable|numeric',
            'ponto_energia_agua' => 'nullable|string',
            
            // Suficiência de instalações
            'tomadas_suficientes' => 'nullable|boolean',
            'luminarias_suficientes' => 'nullable|boolean',
            'pontos_rede_suficientes' => 'nullable|boolean',
            'pontos_telefone_suficientes' => 'nullable|boolean',
            'pontos_ar_condicionado_suficientes' => 'nullable|boolean',
            'pontos_hidraulicos_suficientes' => 'nullable|boolean',
            'pontos_sanitarios_suficientes' => 'nullable|boolean',
            
            // Acabamentos
            'piso' => 'nullable|string|max:255',
            'parede' => 'nullable|string|max:255',
            'esquadrias' => 'nullable|string|max:255',
            'loucas_metais' => 'nullable|string|max:255',
            'forro_lage' => 'nullable|string|max:255',
            'cobertura' => 'nullable|string|max:255',
            'pintura' => 'nullable|string|max:255',
            
            // Equipamentos de segurança
            'extintor_po_quimico' => 'nullable|string|max:255',
            'extintor_co2' => 'nullable|string|max:255',
            'extintor_agua' => 'nullable|string|max:255',
            'placa_incendio' => 'nullable|string|max:255',
        ]);
        
        // Garantir que a unidade_id está correta
        $validatedData['unidade_id'] = $unidade->id;
        
        // Criar o registro de informações
        $informacoes = InformacoesUnidade::create($validatedData);
        
        // Atualizar o status da unidade para pendente de avaliação
        $unidade->update(['status' => 'pendente_avaliacao']);
        
        return redirect()->back()->with('flash.banner', 'Informações estruturais foram salvas com sucesso.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InformacoesUnidade $informacoesUnidade)
    {
        // Buscar a unidade e o time
        $unidade = Unidade::findOrFail($informacoesUnidade->unidade_id);
        $team = Team::findOrFail($unidade->team_id);
        
        // Verificar permissão de atualização
        Gate::authorize('update', $team);
        
        // Validar os dados
        $validatedData = $request->validate([
            // Características da via e serviços
            'pavimentacao_rua' => 'nullable|string|max:255',
            'padrao_energia' => 'nullable|string|max:255',
            'subestacao' => 'nullable|string|max:255',
            'gerador_energia' => 'nullable|string|max:255',
            'para_raio' => 'nullable|string|max:255',
            'caixa_dagua' => 'nullable|string|max:255',
            'internet_cabeada' => 'nullable|string|max:255',
            'internet_provedor' => 'nullable|string|max:255',
            'telefone_fixo' => 'nullable|string|max:20',
            'telefone_movel' => 'nullable|string|max:20',
            
            // Características do imóvel
            'tipo_imovel' => 'nullable|string|max:255',
            'contrato_locacao_id' => 'nullable|string|max:255',
            'responsavel_locacao_cessao' => 'nullable|string|max:255',
            'escritura_publica' => 'nullable|string|max:255',
            
            // Características estruturais
            'area_aproximada_unidade' => 'nullable|numeric',
            'area_aproximada_terreno' => 'nullable|numeric',
            'qtd_pavimentos' => 'nullable|numeric',
            'cercado_muros' => 'nullable|boolean',
            'estacionamento_interno' => 'nullable|boolean',
            'estacionamento_externo' => 'nullable|boolean',
            'recuo_frontal' => 'nullable|numeric',
            'recuo_lateral' => 'nullable|numeric',
            'recuo_fundos' => 'nullable|numeric',
            
            // Quantitativos de espaços e instalações
            'qtd_recepcao' => 'nullable|integer',
            'qtd_wc_publico' => 'nullable|integer',
            'qtd_gabinetes' => 'nullable|integer',
            'qtd_sala_oitiva' => 'nullable|integer',
            'qtd_wc_servidores' => 'nullable|integer',
            'qtd_alojamento_masculino' => 'nullable|integer',
            'qtd_wc_alojamento_masculino' => 'nullable|integer',
            'qtd_alojamento_feminino' => 'nullable|integer',
            'qtd_wc_alojamento_feminino' => 'nullable|integer',
            'qtd_xadrez_masculino' => 'nullable|integer',
            'area_xadrez_masculino' => 'nullable|numeric',
            'qtd_xadrez_feminino' => 'nullable|integer',
            'area_xadrez_feminino' => 'nullable|numeric',
            'qtd_sala_identificacao' => 'nullable|integer',
            'qtd_cozinha' => 'nullable|integer',
            'qtd_area_servico' => 'nullable|integer',
            'qtd_deposito_apreensao' => 'nullable|integer',
            
            // Suficiência de instalações
            'ponto_energia_agua' => 'nullable|string',
            'tomadas_suficientes' => 'nullable|boolean',
            'luminarias_suficientes' => 'nullable|boolean',
            'pontos_rede_suficientes' => 'nullable|boolean',
            'pontos_telefone_suficientes' => 'nullable|boolean',
            'pontos_ar_condicionado_suficientes' => 'nullable|boolean',
            'pontos_hidraulicos_suficientes' => 'nullable|boolean',
            'pontos_sanitarios_suficientes' => 'nullable|boolean',
            
            // Acabamentos
            'piso' => 'nullable|string|max:255',
            'parede' => 'nullable|string|max:255',
            'esquadrias' => 'nullable|string|max:255',
            'loucas_metais' => 'nullable|string|max:255',
            'forro_lage' => 'nullable|string|max:255',
            'cobertura' => 'nullable|string|max:255',
            'pintura' => 'nullable|string|max:255',
            
            // Equipamentos de segurança
            'extintor_po_quimico' => 'nullable|string|max:255',
            'extintor_co2' => 'nullable|string|max:255',
            'extintor_agua' => 'nullable|string|max:255',
            'placa_incendio' => 'nullable|string|max:255',
        ]);
        
        // Atualizar o registro de informações
        $informacoesUnidade->update($validatedData);
        
        // Atualizar o status da unidade para pendente de avaliação
        if (!auth()->user()->hasTeamRole($team, 'admin')) {
            $unidade->update(['status' => 'pendente_avaliacao']);
        }
        
        return redirect()->back()->with('flash.banner', 'Informações estruturais foram atualizadas com sucesso.');
    }
}