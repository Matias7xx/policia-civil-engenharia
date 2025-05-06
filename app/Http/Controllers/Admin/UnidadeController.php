<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvaliacaoUnidade;
use App\Models\ContratoLocacaoUnidade;
use Illuminate\Support\Facades\Storage;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Helpers\RoleHelper;
use App\Models\Orgao;
use Illuminate\Support\Str;

class UnidadeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Verificar se o usuário é SuperAdmin
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        // Iniciar a consulta base
        $unidadesQuery = Unidade::with(['team:id,name', 'avaliacoes']);

        if ($request->has('search') && $request->search) {
            $searchTerms = array_filter(explode(' ', trim($request->search)));
            $unidadesQuery->where(function ($query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $query->where(function ($subQuery) use ($term) {
                        $subQuery->whereRaw('LOWER(nome) LIKE ?', ['%' . strtolower($term) . '%'])
                                 ->orWhereRaw('LOWER(cidade) LIKE ?', ['%' . strtolower($term) . '%'])
                                 ->orWhereRaw('LOWER(srpc) LIKE ?', ['%' . strtolower($term) . '%'])
                                 ->orWhereRaw('LOWER(dspc) LIKE ?', ['%' . strtolower($term) . '%']);
                    });
                }
            });
        }

        if ($request->has('status') && $request->status !== 'todos') {
            $unidadesQuery->where('status', $request->status);
        }

        if ($request->has('nota') && $request->nota !== 'todas') {
            $unidadesQuery->whereHas('avaliacoes', function ($query) use ($request) {
                $query->where('nota_geral', $request->nota);
            });
        }

        // Paginar resultados (10 por página)
        $unidades = $unidadesQuery->orderBy('status')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($unidade) {
                $avaliacao = $unidade->avaliacoes()->latest()->first();
                return [
                    'id' => $unidade->id,
                    'nome' => $unidade->nome,
                    'codigo' => $unidade->codigo,
                    'cidade' => $unidade->cidade ?? 'N/A',
                    'srpc' => $unidade->srpc ?? 'N/A',
                    'dspc' => $unidade->dspc ?? 'N/A',
                    'status' => $unidade->status,
                    'status_formatado' => $unidade->status_formatado,
                    'nota_geral' => $avaliacao ? $avaliacao->nota_geral : null,
                    'created_at' => $unidade->created_at->format('d/m/Y'),
                    'team' => $unidade->team ? [
                        'id' => $unidade->team->id,
                        'name' => $unidade->team->name,
                    ] : null,
                ];
            });

        // Preservar os parâmetros de consulta na paginação
        $unidades->appends($request->all());

        // Obter opções para os filtros
        $statusOptions = [
            ['key' => 'todos', 'name' => 'Todos'],
            ['key' => 'pendente_avaliacao', 'name' => 'Pendente de Avaliação'],
            ['key' => 'aprovada', 'name' => 'Aprovado'],
            ['key' => 'reprovada', 'name' => 'Reprovado'],
            ['key' => 'em_revisao', 'name' => 'Em Revisão'],
        ];

        $notaOptions = [
            ['key' => 'todas', 'name' => 'Todas'],
            ['key' => '10.0', 'name' => '10.0 (A)'],
            ['key' => '9.0', 'name' => '9.0 (B)'],
            ['key' => '8.0', 'name' => '8.0 (C)'],
            ['key' => '7.0', 'name' => '7.0 (D)'],
            ['key' => '6.0', 'name' => '6.0 (E)'],
            ['key' => '5.0', 'name' => '5.0 (F)'],
            ['key' => '4.0', 'name' => '4.0 (G)'],
            ['key' => '3.0', 'name' => '3.0 (H)'],
            ['key' => '2.0', 'name' => '2.0 (I)'],
            ['key' => '1.0', 'name' => '1.0 (J)'],
        ];

        return Inertia::render('Admin/Unidades/Index', [
            'unidades' => $unidades,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'todos',
                'nota' => $request->nota ?? 'todas',
            ],
            'statusOptions' => $statusOptions,
            'notaOptions' => $notaOptions,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!RoleHelper::isSuperAdmin($user)) {
            abort(403, 'Acesso não autorizado');
        }

        $unidade = Unidade::findOrFail($id);
        $unidade->load([
            'team.owner',
            'avaliacoes.avaliador',
            'acessibilidade',
            'informacoes',
            'midias.midiaTipo',
            'orgaoCompartilhado',
            'contratoLocacao',
        ]);

        $orgaos = Orgao::all()->map(function ($orgao) {
            return [
                'id' => $orgao->id,
                'nome' => $orgao->nome,
            ];
        });

        return Inertia::render('Admin/Unidades/Show', [
            'team' => $unidade->team,
            'unidade' => $unidade,
            'acessibilidade' => $unidade->acessibilidade,
            'informacoes' => $unidade->informacoes,
            'midias' => $unidade->midias,
            'orgaos' => $orgaos,
            'permissions' => [
                'canEdit' => RoleHelper::isSuperAdmin($user),
            ],
            'avaliacoes' => $unidade->avaliacoes->map(function ($avaliacao) {
                return [
                    'id' => $avaliacao->id,
                    'status' => $avaliacao->status,
                    'nota_geral' => $avaliacao->nota_geral,
                    'nota_estrutura' => $avaliacao->nota_estrutura,
                    'nota_acessibilidade' => $avaliacao->nota_acessibilidade,
                    'nota_conservacao' => $avaliacao->nota_conservacao,
                    'observacoes' => $avaliacao->observacoes,
                    'avaliador' => $avaliacao->avaliador ? [
                        'name' => $avaliacao->avaliador->name,
                    ] : null,
                    'created_at' => $avaliacao->created_at,
                ];
            }),
            'isSuperAdmin' => RoleHelper::isSuperAdmin($user),
        ]);
    }

    /**
     * Update the contract details for a locado unit.
     */
    public function updateContrato(Request $request, $id)
{
    $user = $request->user();
    if (!RoleHelper::isSuperAdmin($user)) {
        abort(403, 'Acesso não autorizado');
    }

    $unidade = Unidade::findOrFail($id);

    if ($unidade->tipo_judicial !== 'locado') {
        return back()->with('error', 'Unidade não é do tipo locado.');
    }

    $validated = $request->validate([
        'nome_proprietario' => 'required|string|max:255',
        'cpf_cnpj' => 'required|string|min:11|max:14',
        'telefone' => 'nullable|string|min:10|max:11',
        'valor_locacao' => 'required|numeric|min:0',
        'data_inicio' => 'required|date',
        'data_fim' => 'nullable|date|after_or_equal:data_inicio',
        'anexo' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
    ]);

    $contrato = $unidade->contratoLocacao ?: new ContratoLocacaoUnidade(['unidade_id' => $unidade->id]);

    // Limpar caracteres não numéricos
    $cleanedCpfCnpj = preg_replace('/\D/', '', $validated['cpf_cnpj']);
    $cleanedTelefone = $validated['telefone'] ? preg_replace('/\D/', '', $validated['telefone']) : null;

     // Validar comprimento após limpeza
     if (strlen($cleanedCpfCnpj) < 11 || strlen($cleanedCpfCnpj) > 14) {
        return redirect()->back()->withErrors(['cpf_cnpj' => 'O CPF/CNPJ deve conter entre 11 e 14 dígitos numéricos.'])->withInput();
    }
    if ($cleanedTelefone && strlen($cleanedTelefone) !== 11) {
        return redirect()->back()->withErrors(['telefone' => 'O telefone deve conter exatamente 11 dígitos numéricos.'])->withInput();
    }

    $contrato->fill([
        'nome_proprietario' => $validated['nome_proprietario'],
        'cpf_cnpj' => $cleanedCpfCnpj,
        'telefone' => $cleanedTelefone,
        'valor_locacao' => $validated['valor_locacao'],
        'data_inicio' => $validated['data_inicio'],
        'data_fim' => $validated['data_fim'],
    ]);

    if ($request->hasFile('anexo')) {
        $nomeUnidade = Str::slug($unidade->nome);
        $path = "contratos/{$nomeUnidade}";
        if ($contrato->anexo) {
            Storage::disk('public')->delete($contrato->anexo);
        }
        $anexoPath = $request->file('anexo')->store($path, 'public');
        $contrato->anexo = $anexoPath;
    }

    $contrato->save();

    $unidade->load('contratoLocacao');

    return back()->with('success', 'Contrato atualizado com sucesso.');
}

    public function anexo($id)
    {
        $user = Auth::user();
        if (!RoleHelper::isSuperAdmin($user)) {
            abort(403, 'Acesso não autorizado');
        }

        $unidade = Unidade::findOrFail($id);
        $contrato = $unidade->contratoLocacao;

        if (!$contrato || !$contrato->anexo) {
            abort(404, 'Anexo não encontrado.');
        }

        $filePath = storage_path('app/public/' . $contrato->anexo);

        if (!file_exists($filePath)) {
            abort(404, 'Arquivo não encontrado.');
        }

        return response()->file($filePath);
    }

    public function updateCessao(Request $request, $id)
    {
        $user = $request->user();
        if (!RoleHelper::isSuperAdmin($user)) {
            abort(403, 'Acesso não autorizado');
        }

        $unidade = Unidade::findOrFail($id);

        if ($unidade->tipo_judicial !== 'cedido') {
            return back()->with('error', 'Unidade não é do tipo cedido.');
        }

        $validated = $request->validate([
            'orgao_cedente' => 'required|string|max:255',
            'termo_cessao' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'prazo_cessao' => 'required|date',
        ]);

        $unidade->update([
            'orgao_cedente' => $validated['orgao_cedente'],
            'prazo_cessao' => $validated['prazo_cessao'],
        ]);

        if ($request->hasFile('termo_cessao')) {
            // Sanitizar o nome da unidade para evitar caracteres inválidos
            $nomeUnidade = Str::slug($unidade->nome);
            $path = "cessoes/{$nomeUnidade}";

            // Remover termo antigo, se existir
            if ($unidade->termo_cessao) {
                Storage::disk('public')->delete($unidade->termo_cessao);
            }

            // Armazenar novo termo
            $termoPath = $request->file('termo_cessao')->store($path, 'public');
            $unidade->termo_cessao = $termoPath;
            $unidade->save();
        }

        return back()->with('success', 'Dados de cessão atualizados com sucesso.');
    }

    public function termoCessao($id)
    {
        $user = Auth::user();
        if (!RoleHelper::isSuperAdmin($user)) {
            abort(403, 'Acesso não autorizado');
        }

        $unidade = Unidade::findOrFail($id);

        if (!$unidade->termo_cessao) {
            abort(404, 'Termo de cessão não encontrado.');
        }

        $filePath = storage_path('app/public/' . $unidade->termo_cessao);

        if (!file_exists($filePath)) {
            abort(404, 'Arquivo não encontrado.');
        }

        return response()->file($filePath);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!RoleHelper::isSuperAdmin($user)) {
            abort(403, 'Acesso não autorizado');
        }

        $unidade = Unidade::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pendente_avaliacao,aprovada,reprovada,em_revisao',
            'rejection_reason' => 'required_if:status,reprovada|string|max:1000|nullable',
        ]);

        $unidade->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['status'] === 'reprovada' ? $validated['rejection_reason'] : null,
        ]);

        return back()->with('success', 'Status atualizado com sucesso.');
    }
}