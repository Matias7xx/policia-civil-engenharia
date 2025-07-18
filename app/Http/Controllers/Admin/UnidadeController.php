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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Helpers\StorageHelper;

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

    // Subconsulta para identificar os IDs das avaliações mais recentes para cada unidade
    $ultimasAvaliacoesQuery = DB::table('avaliacoes_unidade as a1')
        ->select('a1.id')
        ->whereRaw('a1.id = (SELECT MAX(a2.id) FROM avaliacoes_unidade as a2 WHERE a2.unidade_id = a1.unidade_id)');

    // Iniciar a consulta base, filtrando apenas unidades com is_draft = false
    // e adicionando um LEFT JOIN para as avaliações mais recentes
    $unidadesQuery = Unidade::select('unidades.*')
        ->leftJoin('avaliacoes_unidade', function ($join) use ($ultimasAvaliacoesQuery) {
            $join->on('unidades.id', '=', 'avaliacoes_unidade.unidade_id')
                 ->whereIn('avaliacoes_unidade.id', $ultimasAvaliacoesQuery);
        })
        ->with(['team:id,name'])
        ->where('unidades.is_draft', false);

    // Aplicar filtro de busca textual
    if ($request->has('search') && $request->search) {
        $searchTerms = array_filter(explode(' ', trim($request->search)));
        $unidadesQuery->where(function ($query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->whereRaw('LOWER(unidades.nome) LIKE ?', ['%' . strtolower($term) . '%'])
                             ->orWhereRaw('LOWER(unidades.cidade) LIKE ?', ['%' . strtolower($term) . '%'])
                             ->orWhereRaw('LOWER(unidades.srpc) LIKE ?', ['%' . strtolower($term) . '%'])
                             ->orWhereRaw('LOWER(unidades.dspc) LIKE ?', ['%' . strtolower($term) . '%']);
                });
            }
        });
    }

    // Aplicar filtro por status
    if ($request->has('status') && $request->status !== 'todos') {
        $unidadesQuery->where('unidades.status', $request->status);
    }

    if ($request->has('nota') && $request->nota !== 'todas') {
        $notaFiltro = (string)$request->nota;
        
        // Definir os intervalos de notas por classificação de letra
        $intervalos = [
            '10.0' => [9.5, 10.0],  // A+ (9.5-10.0)
            '9.0' => [9.0, 9.4],    // A (9.0-9.4)
            '8.0' => [8.0, 8.9],    // B (8.0-8.9)
            '7.0' => [7.0, 7.9],    // C (7.0-7.9)
            '6.0' => [6.0, 6.9],    // D (6.0-6.9)
            '5.0' => [5.0, 5.9],    // E (5.0-5.9)
            '4.0' => [4.0, 4.9],    // F (4.0-4.9)
            '3.0' => [3.0, 3.9],    // G (3.0-3.9)
            '2.0' => [2.0, 2.9],    // H (2.0-2.9)
            '1.0' => [1.0, 1.9],    // I (1.0-1.9)
            '0.0' => [0.0, 0.9],    // J (0.0-0.9)
        ];
        
        // Verificar se a nota de filtro existe nos intervalos definidos
        if (isset($intervalos[$notaFiltro])) {
            list($min, $max) = $intervalos[$notaFiltro];
            $unidadesQuery->whereBetween('avaliacoes_unidade.nota_geral', [$min, $max]);
        }
    }

    // Selecionar colunas adicionais da última avaliação
    $unidadesQuery->addSelect([
        'avaliacoes_unidade.nota_geral',
        'avaliacoes_unidade.nota_estrutura',
        'avaliacoes_unidade.nota_acessibilidade',
        'avaliacoes_unidade.created_at as avaliacao_data'
    ]);

    // Paginar resultados (10 por página)
    $unidades = $unidadesQuery->orderBy('unidades.status')
        ->orderBy('unidades.created_at', 'desc')
        ->paginate(10)
        ->through(function ($unidade) {
            return [
                'id' => $unidade->id,
                'nome' => $unidade->nome,
                'codigo' => $unidade->codigo,
                'cidade' => $unidade->cidade ?? 'N/A',
                'srpc' => $unidade->srpc ?? 'N/A',
                'dspc' => $unidade->dspc ?? 'N/A',
                'status' => $unidade->status,
                'status_formatado' => $unidade->status_formatado,
                'nota_geral' => $unidade->nota_geral,
                'created_at' => $unidade->created_at->format('d/m/Y'),
                'team' => $unidade->team ? [
                    'id' => $unidade->team->id,
                    'name' => $unidade->team->name,
                ] : null,
            ];
        });

    // Preservar os parâmetros de consulta na paginação
    $unidades->appends($request->all());

    // Opções para os filtros
    $statusOptions = [
        ['key' => 'todos', 'name' => 'Todos'],
        ['key' => 'pendente_avaliacao', 'name' => 'Pendente de Avaliação'],
        ['key' => 'aprovada', 'name' => 'Aprovado'],
        ['key' => 'reprovada', 'name' => 'Reprovado'],
        ['key' => 'em_revisao', 'name' => 'Em Revisão'],
        ['key' => '', 'name' => 'Sem Cadastro']
    ];

    $notaOptions = [
        ['key' => 'todas', 'name' => 'Todas'],
        ['key' => '10.0', 'name' => '9.5~10.0 (A+)'], /* 9.5-10.0 (A+) - Excelente */
        ['key' => '9.0', 'name' => '9.0~9.4 (A)'], /* 9.0-9.4 (A) - Excelente */
        ['key' => '8.0', 'name' => '8.0~8.9 (B)'],
        ['key' => '7.0', 'name' => '7.0~7.9 (C)'],
        ['key' => '6.0', 'name' => '6.0~6.9 (D)'], /* 6.0-6.9 (D) - Satisfatório */
        ['key' => '5.0', 'name' => '5.0~5.9 (E)'],
        ['key' => '4.0', 'name' => '4.0~4.9 (F)'],
        ['key' => '3.0', 'name' => '3.0~3.9 (G)'],
        ['key' => '2.0', 'name' => '2.0~2.9 (H)'],
        ['key' => '1.0', 'name' => '1.0~1.9 (I)'], /* 1.0-1.9 (I) - Péssimo */
        ['key' => '0.0', 'name' => '0.0~0.9 (J)'], /* 0.0-0.9 (J) - Crítico */
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

        $unidade = Unidade::with([
            'team.owner',
            'avaliacoes.avaliador',
            'acessibilidade',
            'informacoes',
            'midias.midiaTipo',
            'contratoLocacao',
            'orgaosCompartilhados',
        ])->findOrFail($id);

        $orgaos = Orgao::all()->map(function ($orgao) {
            return [
                'id' => $orgao->id,
                'nome' => $orgao->nome,
            ];
        });

        // Converte explicitamente para array, forçando a inclusão da relação orgaosCompartilhados
        $unidadeData = $unidade->toArray();
        $unidadeData['orgaosCompartilhados'] = $unidade->orgaosCompartilhados->toArray();

        return Inertia::render('Admin/Unidades/Show', [
            'team' => $unidade->team,
            'unidade' => $unidadeData,
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
            'telefone' => 'nullable|string|min:10|max:15',
            'valor_locacao' => 'required|numeric|min:0',
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'anexo' => 'nullable|file|mimes:pdf|max:10240',
        ], [
            'anexo.mimes' => 'O arquivo deve ser um PDF válido.',
            'anexo.max' => 'O arquivo não pode exceder 10MB.',
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
            // Remover contrato anterior do MinIO
            if ($contrato->anexo) {
                StorageHelper::removerArquivo($contrato->anexo);
            }
            
            // Salvar novo contrato no MinIO
            $nomeArquivo = 'contrato_locacao.pdf';
            $anexoPath = StorageHelper::salvarDocumento($unidade, $request->file('anexo'), $nomeArquivo);
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

        if (!StorageHelper::arquivoExiste($contrato->anexo)) {
            abort(404, 'Arquivo não encontrado no servidor.');
        }

        try {
            $conteudo = StorageHelper::obterArquivo($contrato->anexo);
            
            return response($conteudo, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="contrato_locacao.pdf"',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        } catch (\Exception $e) {
            abort(500, 'Erro ao carregar arquivo');
        }
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
            'termo_cessao' => 'nullable|file|mimes:pdf|max:10240',
            'prazo_cessao' => 'required|date',
        ], [
            'termo_cessao.mimes' => 'O arquivo deve ser um PDF válido.',
            'termo_cessao.max' => 'O arquivo não pode exceder 10MB.',
        ]);

        $unidade->update([
            'orgao_cedente' => $validated['orgao_cedente'],
            'prazo_cessao' => $validated['prazo_cessao'],
        ]);

        if ($request->hasFile('termo_cessao')) {
            // Remover termo anterior do MinIO
            if ($unidade->termo_cessao) {
                StorageHelper::removerArquivo($unidade->termo_cessao);
            }

            // Salvar novo termo no MinIO
            $nomeArquivo = 'termo_cessao.pdf';
            $termoPath = StorageHelper::salvarDocumento($unidade, $request->file('termo_cessao'), $nomeArquivo);
            $unidade->termo_cessao = $termoPath;
            $unidade->save();
        }

        return back()->with('success', 'Dados de cessão atualizados com sucesso.');
    }

    public function termoCessao($id)
    {
        $user = Auth::user();
        if (!RoleHelper::isSuperAdmin($user)) {
            abort(403, 'Acesso não autorizada');
        }

        $unidade = Unidade::findOrFail($id);

        if (!$unidade->termo_cessao) {
            abort(404, 'Termo de cessão não encontrado.');
        }

        if (!StorageHelper::arquivoExiste($unidade->termo_cessao)) {
            abort(404, 'Arquivo não encontrado no servidor.');
        }

        try {
            $conteudo = StorageHelper::obterArquivo($unidade->termo_cessao);
            
            return response($conteudo, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="termo_cessao.pdf"',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        } catch (\Exception $e) {
            abort(500, 'Erro ao carregar arquivo');
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        if (!RoleHelper::isSuperAdmin($user)) {
            abort(403, 'Acesso não autorizado');
        }

        $unidade = Unidade::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pendente_avaliacao,aprovada,reprovada,em_revisao,sem_cadastro',
            'rejection_reason' => 'required_if:status,reprovada|string|max:1000|nullable',
        ]);

        $unidade->update([
            'status' => $validated['status'],
            'rejection_reason' => $validated['status'] === 'reprovada' ? $validated['rejection_reason'] : null,
        ]);

        return back()->with('success', 'Status atualizado com sucesso.');
    }
}