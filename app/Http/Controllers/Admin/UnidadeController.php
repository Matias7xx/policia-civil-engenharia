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
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        $unidadesQuery = Unidade::with(['team']);

        // Aplicar filtros de busca
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

        // Aplicar filtros de status
        if ($request->has('status') && $request->status !== 'todos') {
            if ($request->status === '') {
                // Filtrar por "Sem Cadastro" (is_draft = true)
                $unidadesQuery->where('is_draft', true);
            } else {
                $unidadesQuery->where('status', $request->status);
            }
        }

        // Aplicar filtros de nota
        if ($request->has('nota') && $request->nota !== 'todas') {
            $notaMin = (float) $request->nota;
            if ($notaMin == 10.0) {
                $unidadesQuery->whereBetween('nota_geral', [9.5, 10.0]);
            } elseif ($notaMin == 9.0) {
                $unidadesQuery->whereBetween('nota_geral', [9.0, 9.4]);
            } elseif ($notaMin == 0.0) {
                $unidadesQuery->whereBetween('nota_geral', [0.0, 0.9]);
            } else {
                $unidadesQuery->whereBetween('nota_geral', [$notaMin, $notaMin + 0.9]);
            }
        }

        // Adicionar informações sobre unidades em rascunho (is_draft = true)
        $unidadesDraftQuery = Unidade::with(['team'])
            ->where('is_draft', true);

        // Aplicar mesmos filtros de busca para rascunhos se necessário
        if ($request->has('search') && $request->search && $request->status === '') {
            $searchTerms = array_filter(explode(' ', trim($request->search)));
            $unidadesDraftQuery->where(function ($query) use ($searchTerms) {
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

        // Combinar resultados se filtro de status for "Sem Cadastro"
        if ($request->status === '') {
            $unidades = $unidadesDraftQuery->orderBy('nome')
                ->paginate(10)
                ->through(function ($unidade) {
                    return [
                        'id' => $unidade->id,
                        'nome' => $unidade->nome ?? 'N/A',
                        'cidade' => $unidade->cidade ?? 'N/A',
                        'srpc' => $unidade->srpc ?? 'N/A',
                        'dspc' => $unidade->dspc ?? 'N/A',
                        'status' => null, // Rascunho não tem status
                        'status_formatado' => 'Sem Cadastro',
                        'nota_geral' => null,
                        'is_draft' => $unidade->is_draft,
                        'created_at' => $unidade->created_at->format('d/m/Y'),
                        'team' => $unidade->team ? [
                            'id' => $unidade->team->id,
                            'name' => $unidade->team->name,
                        ] : null,
                    ];
                });
        } else {
            $unidades = $unidadesQuery->orderBy('nome')
                ->paginate(10)
                ->through(function ($unidade) {
                    return [
                        'id' => $unidade->id,
                        'nome' => $unidade->nome ?? 'N/A',
                        'cidade' => $unidade->cidade ?? 'N/A',
                        'srpc' => $unidade->srpc ?? 'N/A',
                        'dspc' => $unidade->dspc ?? 'N/A',
                        'status' => $unidade->status,
                        'status_formatado' => $unidade->status_formatado,
                        'nota_geral' => $unidade->nota_geral,
                        'is_draft' => $unidade->is_draft,
                        'created_at' => $unidade->created_at->format('d/m/Y'),
                        'team' => $unidade->team ? [
                            'id' => $unidade->team->id,
                            'name' => $unidade->team->name,
                        ] : null,
                    ];
                });
        }

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
            ['key' => '10.0', 'name' => '9.5~10.0 (A+)'],
            ['key' => '9.0', 'name' => '9.0~9.4 (A)'],
            ['key' => '8.0', 'name' => '8.0~8.9 (B)'],
            ['key' => '7.0', 'name' => '7.0~7.9 (C)'],
            ['key' => '6.0', 'name' => '6.0~6.9 (D)'],
            ['key' => '5.0', 'name' => '5.0~5.9 (E)'],
            ['key' => '4.0', 'name' => '4.0~4.9 (F)'],
            ['key' => '3.0', 'name' => '3.0~3.9 (G)'],
            ['key' => '2.0', 'name' => '2.0~2.9 (H)'],
            ['key' => '1.0', 'name' => '1.0~1.9 (I)'],
            ['key' => '0.0', 'name' => '0.0~0.9 (J)'],
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