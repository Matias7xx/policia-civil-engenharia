<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Orgao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Helpers\RoleHelper;

class OrgaoController extends Controller
{
    
    public function index(Request $request)
    {
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        $orgaosQuery = Orgao::query();

        if ($request->has('search') && $request->search) {
            $searchTerms = array_filter(explode(' ', trim($request->search)));
            $orgaosQuery->where(function ($query) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $query->whereRaw('LOWER(nome) LIKE ?', ['%' . strtolower($term) . '%']);
                }
            });
        }

        if ($request->has('status') && $request->status !== 'todos') {
            $orgaosQuery->where('status', $request->status);
        }

        $orgaos = $orgaosQuery->orderBy('nome')
            ->paginate(10)
            ->through(function ($orgao) {
                return [
                    'id' => $orgao->id,
                    'nome' => $orgao->nome,
                    'status' => $orgao->status ?? 'N/A',
                    'created_at' => $orgao->created_at->format('d/m/Y'),
                ];
            });

        $orgaos->appends($request->all());

        $statusOptions = [
            ['key' => 'todos', 'name' => 'Todos'],
            ['key' => 'ativo', 'name' => 'Ativo'],
            ['key' => 'inativo', 'name' => 'Inativo'],
        ];

        return Inertia::render('Admin/Orgaos/Index', [
            'orgaos' => $orgaos,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'todos',
            ],
            'statusOptions' => $statusOptions,
        ]);
    }

    public function create()
    {
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        return Inertia::render('Admin/Orgaos/Create');
    }
    
    public function store(Request $request)
    {
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'status' => 'nullable|in:ativo,inativo',
        ]);

        Orgao::create($validated);

        return redirect()->route('admin.orgaos.index')->with('success', 'Órgão criado com sucesso.');
    }

    public function show($id)
    {
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        $orgao = Orgao::with('unidadesCompartilhadas')->findOrFail($id);

        return Inertia::render('Admin/Orgaos/Show', [
            'orgao' => [
                'id' => $orgao->id,
                'nome' => $orgao->nome,
                'status' => $orgao->status,
                'unidades' => $orgao->unidadesCompartilhadas->map(function ($unidade) {
                    return [
                        'id' => $unidade->id,
                        'nome' => $unidade->nome,
                        'cidade' => $unidade->cidade,
                    ];
                }),
            ],
        ]);
    }

    public function edit($id)
    {
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        $orgao = Orgao::findOrFail($id);

        return Inertia::render('Admin/Orgaos/Edit', [
            'orgao' => [
                'id' => $orgao->id,
                'nome' => $orgao->nome,
                'status' => $orgao->status,
            ],
        ]);
    }
    
    public function update(Request $request, $id)
    {
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        $orgao = Orgao::findOrFail($id);

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'status' => 'nullable|in:ativo,inativo',
        ]);

        $orgao->update($validated);

        return redirect()->route('admin.orgaos.index')->with('success', 'Órgão atualizado com sucesso.');
    }

    public function destroy($id)
    {
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Ação não autorizada.');
        }

        $orgao = Orgao::findOrFail($id);
        $orgao->delete();

        return redirect()->route('admin.orgaos.index')->with('success', 'Órgão excluído com sucesso.');
    }
}