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
        $unidade = Unidade::where('team_id', $teamId)->first();

        // Se não existir unidade, inicializar um objeto vazio para o formulário
        if (!$unidade) {
            $unidade = new Unidade();
            $unidade->team_id = $teamId;
            $unidade->nome = $team->name; // Valor inicial para o formulário
            $unidade->is_draft = true; // Marcado como rascunho (mas não salvo ainda)
        } else {
            $unidade->load('acessibilidade', 'informacoes', 'midias', 'contratoLocacao');
        }

        return Inertia::render('Unidades/Create', [
            'team' => $team,
            'unidade' => $unidade,
            'acessibilidade' => $unidade->acessibilidade,
            'informacoes' => $unidade->informacoes,
            'midias' => $unidade->midias ?? [],
            'contratoLocacao' => $unidade->contratoLocacao,
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
            'midias.midia_tipo',
            'contratoLocacao',
        ])->findOrFail($unidade);

        $user = auth()->user();
        $isAdmin = $user->isAdmin;

        return Inertia::render('Unidades/Show', [
            'team' => $team,
            'unidade' => $unidade,
            'acessibilidade' => $unidade->acessibilidade,
            'informacoes' => $unidade->informacoes,
            'midias' => $unidade->midias,
            'contratoLocacao' => $unidade->contratoLocacao, // Adicionar esta linha
            'orgaos' => Orgao::all(),
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
            'cidade' => 'required|string|max:255',
            'cep' => 'required|string|max:20',
            'rua' => 'required|string|max:255',
            'numero' => 'nullable|string|max:50',
            'bairro' => 'required|string|max:255',
            'complemento' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefone_1' => 'nullable|string|max:20',
            'telefone_2' => 'nullable|string|max:20',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'tipo_judicial' => 'required|string|in:proprio,locado,cedido',
            'imovel_compartilhado_orgao' => 'boolean',
            'imovel_compartilhado_orgao_id' => 'nullable|exists:orgaos,id',
            'observacoes' => 'nullable|string',
            'numero_medidor_agua' => 'nullable|string|max:50',
            'numero_medidor_energia' => 'nullable|string|max:50',
            'nome_proprietario' => 'required_if:tipo_judicial,locado|string|max:255|nullable',
            'cpf_cnpj' => 'required_if:tipo_judicial,locado|string|max:50|nullable',
            'telefone_proprietario' => 'nullable|string|max:20',
            'valor_locacao' => 'required_if:tipo_judicial,locado|numeric|nullable',
            'data_inicio' => 'required_if:tipo_judicial,locado|date|nullable',
            'data_fim' => 'required_if:tipo_judicial,locado|date|nullable',
            'orgao_cedente' => 'required_if:tipo_judicial,cedido|string|max:255|nullable',
            'termo_cessao' => 'required_if:tipo_judicial,cedido|string|max:255|nullable',
            'prazo_cessao' => 'required_if:tipo_judicial,cedido|date|nullable',
        ]);

        // Verificar permissão de atualização
        if (!auth()->user()->hasTeamPermission($team, 'update')) {
            return redirect()->back()->with('error', 'Você não tem permissão para atualizar esta unidade.');
        }

        // Criar ou atualizar a unidade
        $unidade = Unidade::updateOrCreate(
            ['team_id' => $request->team_id],
            array_merge($validated, ['is_draft' => true])
        );

        if ($request->tipo_judicial === 'locado') {
            $unidade->contratoLocacao()->updateOrCreate(
                ['unidade_id' => $unidade->id],
                [
                    'nome_proprietario' => $request->nome_proprietario,
                    'cpf_cnpj' => $request->cpf_cnpj,
                    'telefone' => $request->telefone_proprietario,
                    'valor_locacao' => $request->valor_locacao,
                    'data_inicio' => $request->data_inicio,
                    'data_fim' => $request->data_fim,
                ]
            );
        }

        // Atualizar o status da unidade para pendente de avaliação
        $unidade->update(['status' => 'pendente_avaliacao']);

        // Redirecionar de volta com uma mensagem de sucesso
        return redirect()->back()->with('success', 'Dados gerais salvos com sucesso.');
    }

    public function saveAcessibilidade(Request $request)
    {
        // Validar os dados
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

        // Encontrar a unidade
        $unidade = Unidade::findOrFail($validated['unidade_id']);

        // Verificar permissão de atualização (incorporado do AcessibilidadeUnidadeController)
        $team = Team::findOrFail($unidade->team_id);
        if (!auth()->user()->hasTeamPermission($team, 'update')) {
            return redirect()->back()->with('error', 'Você não tem permissão para atualizar esta unidade.');
        }

        // Criar ou atualizar o registro de acessibilidade
        $acessibilidade = AcessibilidadeUnidade::updateOrCreate(
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

        // Redirecionar de volta com uma mensagem de sucesso
        return redirect()->back()->with('success', 'Informações de acessibilidade salvas com sucesso.');
    }
        public function saveInformacoesEstruturais(Request $request)
    {
        $validated = $request->validate([
            'unidade_id' => 'required|exists:unidades,id',
            'pavimentacao_rua' => 'required|string|max:255', // Tornar obrigatório, conforme validação client-side
            'padrao_energia' => 'required|string|max:255',   // Tornar obrigatório, conforme validação client-side
            'subestacao' => 'nullable|string|max:255',
            'gerador_energia' => 'nullable|string|max:255',
            'para_raio' => 'nullable|string|max:255',
            'caixa_dagua' => 'nullable|string|max:255',
            'internet_cabeada' => 'nullable|string|max:255',
            'internet_provedor' => 'nullable|string|max:255',
            'telefone_fixo' => 'nullable|string|max:20',
            'telefone_movel' => 'nullable|string|max:20',
            'tipo_imovel' => 'nullable|string|max:255',
            'contrato_locacao_id' => 'nullable|string|max:255',
            'responsavel_locacao_cessao' => 'nullable|string|max:255',
            'escritura_publica' => 'nullable|string|max:255',
            'qtd_pavimentos' => 'nullable|numeric', // Ajustado para numeric (aceita string que pode ser convertida)
            'cercado_muros' => 'boolean',
            'estacionamento_interno' => 'boolean',
            'estacionamento_externo' => 'boolean',
            'recuo_frontal' => 'nullable|numeric',  // Ajustado para numeric
            'recuo_lateral' => 'nullable|numeric',  // Ajustado para numeric
            'recuo_fundos' => 'nullable|numeric',   // Ajustado para numeric
            'qtd_recepcao' => 'nullable|integer',
            'qtd_wc_publico' => 'nullable|integer',
            'qtd_gabinetes' => 'nullable|integer',
            'qtd_sala_oitiva' => 'nullable|integer',
            'qtd_wc_servidores' => 'nullable|integer',
            'qtd_alojamento_masculino' => 'nullable|integer',
            'qtd_wc_alojamento_masculino' => 'nullable|integer',
            'qtd_alojamento_feminino' => 'nullable|integer',
            'qtd_wc_alojamento_feminino' => 'nullable|integer',
            'qtd_celas_carceragem' => 'nullable|integer',
            'qtd_sala_identificacao' => 'nullable|integer',
            'qtd_cozinha' => 'nullable|integer',
            'qtd/area_servico' => 'nullable|integer',
            'qtd_deposito_apreensao' => 'nullable|integer',
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
        ]);

        $unidade = Unidade::findOrFail($request->unidade_id);

        // Verificar permissão de atualização
        $team = Team::findOrFail($unidade->team_id);
        if (!auth()->user()->hasTeamPermission($team, 'update')) {
            return redirect()->back()->with('error', 'Você não tem permissão para atualizar esta unidade.');
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

        if (!$unidade->acessibilidade || !$unidade->informacoes || $unidade->midias->isEmpty()) {
            return redirect()->back()->with('error', 'Todas as abas devem ser preenchidas antes de finalizar o cadastro.');
        }

        \Log::info('Tentativa de finalizar unidade', ['unidade_id' => $unidade->id, 'is_draft_before' => $unidade->is_draft]);
        $unidade->update(['is_draft' => false]);
        \Log::info('Unidade finalizada', ['unidade_id' => $unidade->id, 'is_draft_after' => $unidade->is_draft]);

        // Exibir mensagem antes do redirecionamento
        return redirect()->route('unidades.show', [
            'team' => $unidade->team_id,
            'unidade' => $unidade->id,
        ])->with('success', 'Cadastro finalizado com sucesso.');
    }
}