<?php

namespace App\Http\Controllers;

use App\Models\Unidade;
use App\Models\AcessibilidadeUnidade;
use App\Models\InformacoesUnidade;
use App\Models\Orgao;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class UnidadeController extends Controller
{
    public function create($teamId = null)
    {
        // Se teamId não for fornecido, usar o time atual do usuário
        if (!$teamId) {
            $team = auth()->user()->currentTeam;
            if (!$team) {
                return redirect()->route('teams.index')
                    ->with('error', 'Nenhum time selecionado. Por favor, selecione um time.');
            }
            $teamId = $team->id;
        } else {
            // Validar que teamId é um número
            if (!is_numeric($teamId)) {
                abort(404, 'ID do time inválido.');
            }
            $team = Team::findOrFail($teamId);
        }

        // Verificar se já existe uma unidade para este team
        $unidade = Unidade::where('team_id', $teamId)->with('orgaosCompartilhados')->first();

        // Se não existir unidade, inicializar um objeto vazio para o formulário
        if (!$unidade) {
            $unidade = new Unidade();
            $unidade->team_id = $teamId;
            $unidade->nome = $team->name; // Valor inicial para o formulário
            $unidade->is_draft = true; // Marcado como rascunho (mas não salvo ainda)
        } else {
            $unidade->load('acessibilidade', 'informacoes', 'midias', 'orgaosCompartilhados');
        }

        // Converter para array explicitamente - necessário para persistir órgãos compartilhados na view
        $unidadeData = $unidade->toArray();
        $unidadeData['orgaosCompartilhados'] = $unidade->orgaosCompartilhados->toArray();

        return Inertia::render('Unidades/Create', [
            'team' => $team,
            'unidade' => $unidadeData,
            'acessibilidade' => $unidade->acessibilidade,
            'informacoes' => $unidade->informacoes,
            'midias' => $unidade->midias ?? [],
            'orgaos' => Orgao::ativo()->get(['id', 'nome']),
            'permissions' => [
                'canUpdateTeam' => auth()->user()->hasTeamPermission($team, 'update'),
            ],
        ]);
    }

    public function show($team, $unidade)
    {
        $unidade = Unidade::with([
            'acessibilidade',
            'informacoes',
            'midias.midiaTipo',
            'orgaosCompartilhados', // Garante o carregamento
        ])->findOrFail($unidade);

        $user = auth()->user();
        $isAdmin = $user->isAdmin;

        // Converte explicitamente para array, incluindo a relação
        $unidadeData = $unidade->toArray();
        $unidadeData['orgaosCompartilhados'] = $unidade->orgaosCompartilhados->toArray(); // Força a inclusão

        return Inertia::render('Unidades/Show', [
            'team' => $team,
            'unidade' => $unidadeData,
            'acessibilidade' => $unidade->acessibilidade ? $unidade->acessibilidade->toArray() : null,
            'informacoes' => $unidade->informacoes ? $unidade->informacoes->toArray() : null,
            'midias' => $unidade->midias->toArray(),
            'orgaos' => Orgao::all()->toArray(),
            'permissions' => [
                'canUpdateTeam' => $user->hasTeamPermission($unidade->team, 'update'),
                'isAdmin' => $isAdmin,
                'canDeleteTeam' => $user->hasTeamPermission($unidade->team, 'delete'),
            ],
            'availableRoles' => config('roles.available_roles', []),
            'userPermissions' => [
                'canManageTeamMembers' => $user->hasTeamPermission($unidade->team, 'manage-members'),
            ],
        ]);
    }

    public function saveDadosGerais(Request $request)
    {
        $team = auth()->user()->currentTeam;
        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'nome' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'tipo_estrutural' => 'required|string|in:delegacia,unidade_especializada,instituto,academia,superintendencia,outra',
            'srpc' => 'nullable|string|max:255',
            'dspc' => 'nullable|string|max:255',
            'nivel' => 'nullable|string|max:50',
            'sede' => 'boolean',
            'email' => 'nullable|email|max:255',
            'telefone_1' => 'nullable|string|max:20',
            'telefone_2' => 'nullable|string|max:20',
            'tipo_judicial' => 'required|string|in:proprio,locado,cedido',
            'imovel_compartilhado_orgao' => 'boolean',
            'imovel_compartilhado_orgao_ids' => 'nullable|array',
            'imovel_compartilhado_orgao_ids.*' => 'exists:orgaos,id',
            'observacoes' => 'nullable|string',
            'numero_medidor_agua' => 'nullable|string|max:50',
            'numero_medidor_energia' => 'nullable|string|max:50',
        ]);

        if (!auth()->user()->hasTeamPermission($team, 'update')) {
            return redirect()->back()->with('error', 'Você não tem permissão para atualizar esta unidade.');
        }

        $unidadeData = $validated;
        unset($unidadeData['imovel_compartilhado_orgao_ids']);

        // Verificar se o status é 'reprovada' e alterar para 'pendente_avaliacao'
        $unidade = Unidade::where('team_id', $request->team_id)->first();
        if ($unidade && $unidade->status === 'reprovada') {
            $unidadeData['status'] = 'pendente_avaliacao';
        }

        $unidade = Unidade::updateOrCreate(
            ['team_id' => $request->team_id],
            $unidadeData
        );

        if ($validated['imovel_compartilhado_orgao'] && !empty($validated['imovel_compartilhado_orgao_ids'])) {
            $unidade->orgaosCompartilhados()->sync($validated['imovel_compartilhado_orgao_ids']);
        } else {
            $unidade->orgaosCompartilhados()->detach();
        }

        // Redirecionar para a rota de criação ou edição com a unidade atualizada
        /* $route = $unidade->is_draft ? 'unidades.create' : 'unidades.edit';
        $params = $unidade->is_draft ? ['teamId' => $unidade->team_id] : ['teamId' => $unidade->team_id, 'unidadeId' => $unidade->id];
        return redirect()->route($route, $params)->with('success', 'Dados gerais salvos com sucesso.'); */

        return redirect()->back()->with('success', 'Dados gerais salvos com sucesso.');
    }

    public function saveLocalizacao(Request $request)
    {
        $team = auth()->user()->currentTeam;
        $validated = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'cidade' => 'required|string|max:255',
            'cep' => 'required|string|min:8|max:9',
            'rua' => 'required|string|max:255',
            'numero' => 'nullable|string|max:50',
            'bairro' => 'required|string|max:255',
            'complemento' => 'nullable|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if (!auth()->user()->hasTeamPermission($team, 'update')) {
            return redirect()->back()->with('error', 'Você não tem permissão para atualizar esta unidade.');
        }

        // Verificar se o status é 'reprovada' e alterar para 'pendente_avaliacao'
        $unidade = Unidade::where('team_id', $request->team_id)->first();
        if ($unidade && $unidade->status === 'reprovada') {
            $validated['status'] = 'pendente_avaliacao';
        }

        $unidade = Unidade::updateOrCreate(
            ['team_id' => $request->team_id],
            $validated
        );

        return redirect()->back()->with('success', 'Dados de localização salvos com sucesso.');
    }

    public function saveAcessibilidade(Request $request)
    {
        $validated = $request->validate([
            'unidade_id' => 'required|exists:unidades,id',
            'rampa_acesso' => 'required|boolean',
            'corrimao' => 'required|boolean',
            'piso_tatil' => 'required|boolean',
            'banheiro_adaptado' => 'required|boolean',
            'elevador' => 'required|boolean',
            'sinalizacao_braile' => 'required|boolean',
            'observacoes' => 'nullable|string|max:1000',
        ]);

        $unidade = Unidade::findOrFail($validated['unidade_id']);
        $team = Team::findOrFail($unidade->team_id);
        if (!auth()->user()->hasTeamPermission($team, 'update')) {
            return redirect()->back()->with('error', 'Você não tem permissão para atualizar esta unidade.');
        }

        // Verificar se o status é 'reprovada' e alterar para 'pendente_avaliacao'
        if ($unidade->status === 'reprovada') {
            $unidade->update(['status' => 'pendente_avaliacao']);
        }

        AcessibilidadeUnidade::updateOrCreate(
            ['unidade_id' => $unidade->id],
            [
                'rampa_acesso' => $validated['rampa_acesso'],
                'corrimao' => $validated['corrimao'],
                'piso_tatil' => $validated['piso_tatil'],
                'banheiro_adaptado' => $validated['banheiro_adaptado'],
                'elevador' => $validated['elevador'],
                'sinalizacao_braile' => $validated['sinalizacao_braile'],
                'observacoes' => $validated['observacoes'],
            ]
        );

        return redirect()->back()->with('success', 'Informações de acessibilidade salvas com sucesso.');
    }

    public function saveInformacoesEstruturais(Request $request)
    {
        $validated = $request->validate([
            'unidade_id' => 'required|exists:unidades,id',
            'pavimentacao_rua' => 'required|string|max:255',
            'padrao_energia' => 'nullable|string|max:255',
            'subestacao' => 'nullable|string|max:255',
            'gerador_energia' => 'nullable|string|max:255',
            'para_raio' => 'nullable|string|max:255',
            'caixa_dagua' => 'nullable|string|max:255',
            'internet_cabeada' => 'nullable|string|max:255',
            'internet_provedor' => 'nullable|string|max:255',
            'telefone_fixo' => 'nullable|string|max:20',
            'telefone_movel' => 'nullable|string|max:20',
            'tipo_imovel' => 'nullable|string|max:255',
            'responsavel_locacao_cessao' => 'nullable|string|max:255',
            'escritura_publica' => 'nullable|string|max:255',
            'area_aproximada_unidade' => 'nullable|numeric',
            'area_aproximada_terreno' => 'nullable|numeric',
            'qtd_pavimentos' => 'nullable|numeric',
            'cercado_muros' => 'boolean',
            'estacionamento_interno' => 'boolean',
            'estacionamento_externo' => 'boolean',
            'recuo_frontal' => 'nullable|numeric',
            'recuo_lateral' => 'nullable|numeric',
            'recuo_fundos' => 'nullable|numeric',
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
            'ponto_energia_agua' => 'nullable|string',
            'tomadas_suficientes' => 'boolean',
            'luminarias_suficientes' => 'boolean',
            'pontos_rede_suficientes' => 'boolean',
            'pontos_telefone_suficientes' => 'boolean',
            'pontos_ar_condicionado_suficientes' => 'boolean',
            'pontos_hidraulicos_suficientes' => 'boolean',
            'pontos_sanitarios_suficientes' => 'boolean',
            'piso' => 'nullable|string|max:255',
            'parede' => 'nullable|string|max:255',
            'esquadrias' => 'nullable|string|max:255',
            'loucas_metais' => 'nullable|string|max:255',
            'forro_lage' => 'nullable|string|max:255',
            'cobertura' => 'nullable|string|max:255',
            'pintura' => 'nullable|string|max:255',
            'extintor_po_quimico' => 'nullable|string|max:255',
            'extintor_co2' => 'nullable|string|max:255',
            'extintor_agua' => 'nullable|string|max:255',
            'placa_incendio' => 'nullable|string|max:255',
            'tem_espaco_veiculos_apreendidos' => 'boolean',
            'qtd_max_veiculos_automovel' => 'nullable|integer|min:0',
            'seguranca_local_veiculos' => 'nullable|string|in:sim,nao,parcial',
            'historico_invasao_veiculo' => 'boolean',
            'observacoes_veiculos_apreendidos' => 'nullable|string|max:1000'
        ]);

        $unidade = Unidade::findOrFail($request->unidade_id);
        $team = Team::findOrFail($unidade->team_id);
        if (!auth()->user()->hasTeamPermission($team, 'update')) {
            return redirect()->back()->with('error', 'Você não tem permissão para atualizar esta unidade.');
        }

        // Verificar se o status é 'reprovada' e alterar para 'pendente_avaliacao'
        if ($unidade->status === 'reprovada') {
            $unidade->update(['status' => 'pendente_avaliacao']);
        }

        InformacoesUnidade::updateOrCreate(
            ['unidade_id' => $unidade->id],
            $validated
        );

        return redirect()->back()->with('success', 'Informações estruturais salvas com sucesso.');
    }

    public function finalize(Request $request, $unidade)
    {
        $unidade = Unidade::findOrFail($unidade);

        // Verifica se todas as abas estão preenchidas
        if (!$unidade->acessibilidade || !$unidade->informacoes || $unidade->midias->isEmpty()) {
            return redirect()->back()->with('error', 'Todas as abas devem ser preenchidas antes de finalizar o cadastro.');
        }

        // Verificar se todas as mídias obrigatórias existem
        $tiposObrigatorios = ['foto_frente', 'foto_lateral_1', 'foto_lateral_2', 'foto_fundos', 
                              'foto_medidor_agua', 'foto_medidor_energia'];
        
        $tiposExistentes = $unidade->midias()
            ->join('midia_tipos', 'midias.midia_tipo_id', '=', 'midia_tipos.id')
            ->pluck('midia_tipos.nome')
            ->toArray();
        
        $tiposFaltantes = array_diff($tiposObrigatorios, $tiposExistentes);
        
        if (!empty($tiposFaltantes)) {
            return redirect()->back()->with('error', 'Faltam fotos obrigatórias: ' . implode(', ', $tiposFaltantes));
        }

        // Prosseguir com a finalização, definindo o status como 'pendente_avaliacao'
        $unidade->update([
            'is_draft' => false,
            'status' => 'pendente_avaliacao', // Define o status inicial
        ]);

        return redirect()->route('unidades.show', [
            'team' => $unidade->team_id,
            'unidade' => $unidade->id,
        ])->with('success', 'Cadastro finalizado com sucesso.');
    }

    public function edit($teamId, $unidadeId)
    {
        $team = Team::findOrFail($teamId);
        $unidade = Unidade::where('team_id', $teamId)
            ->with(['acessibilidade', 'informacoes', 'midias', 'orgaosCompartilhados'])
            ->findOrFail($unidadeId);

            // Converter para array explicitamente - necessário para persistir órgãos compartilhados na view
        $unidadeData = $unidade->toArray();
        $unidadeData['orgaosCompartilhados'] = $unidade->orgaosCompartilhados->toArray();

        return Inertia::render('Unidades/Edit', [
            'team' => $team,
            'unidade' => $unidadeData,
            'acessibilidade' => $unidade->acessibilidade,
            'informacoes' => $unidade->informacoes,
            'midias' => $unidade->midias ?? [],
            'orgaos' => Orgao::ativo()->get(['id', 'nome']),
            'permissions' => [
                'canUpdateTeam' => auth()->user()->hasTeamPermission($team, 'update'),
            ],
        ]);
    }
}