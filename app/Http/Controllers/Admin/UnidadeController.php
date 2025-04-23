<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvaliacaoUnidade;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UnidadeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Verificar se o usuário é administrador
        $user = Auth::user();
        $isAdmin = false;
        
        if ($user->currentTeam) {
            $isAdmin = $user->hasTeamRole($user->currentTeam, 'admin');
        }
        
        if (!$isAdmin) {
            abort(403, 'Você não tem permissão para acessar esta página.');
        }
        
        // Buscar todas as unidades com seus times e localização
        $unidades = Unidade::with(['team:id,name', 'avaliacoes'])
            ->orderBy('status')  // Ordenar por status
            ->orderBy('created_at', 'desc')  // Depois por data de criação
            ->get()
            ->map(function ($unidade) {
                return [
                    'id' => $unidade->id,
                    'team_id' => $unidade->team_id,
                    'nome' => $unidade->nome,
                    'codigo' => $unidade->codigo,
                    'cidade' => $unidade->cidade_id ? 'Cidade ' . $unidade->cidade_id : null,
                    'rua' => $unidade->rua,
                    'numero' => $unidade->numero,
                    'bairro' => $unidade->bairro,
                    'status' => $unidade->status,
                    'created_at' => $unidade->created_at->format('d/m/Y'),
                    'has_avaliacao' => $unidade->avaliacoes->count() > 0,
                ];
            });
        
        return Inertia::render('Admin/Unidades/Index', [
            'unidades' => $unidades,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Unidade $unidade)
    {
        // Verificar se o usuário é administrador
        $user = Auth::user();
        $isAdmin = false;
        
        if ($user->currentTeam) {
            $isAdmin = $user->hasTeamRole($user->currentTeam, 'admin');
        }
        
        if (!$isAdmin) {
            abort(403, 'Você não tem permissão para acessar esta página.');
        }
        
        // Carregar informações detalhadas da unidade
        $unidade->load(['team', 'avaliacoes.avaliador']);
        
        // Carregar informações adicionais
        $acessibilidade = $unidade->acessibilidade()->first();
        $informacoes = $unidade->informacoes()->first();
        
        // Encontrar a avaliação mais recente
        $avaliacao = $unidade->avaliacoes()->latest()->first();
        
        return Inertia::render('Admin/Unidades/Show', [
            'unidade' => [
                'id' => $unidade->id,
                'team_id' => $unidade->team_id,
                'nome' => $unidade->nome,
                'codigo' => $unidade->codigo,
                'tipo_estrutural' => $unidade->tipo_estrutural,
                'srpc' => $unidade->srpc,
                'dspc' => $unidade->dspc,
                'nivel' => $unidade->nivel,
                'sede' => $unidade->sede,
                'cidade_id' => $unidade->cidade_id,
                'cep' => $unidade->cep,
                'rua' => $unidade->rua,
                'numero' => $unidade->numero,
                'bairro' => $unidade->bairro,
                'complemento' => $unidade->complemento,
                'email' => $unidade->email,
                'telefone_1' => $unidade->telefone_1,
                'telefone_2' => $unidade->telefone_2,
                'status' => $unidade->status,
                'created_at' => $unidade->created_at->format('d/m/Y'),
                'updated_at' => $unidade->updated_at->format('d/m/Y'),
            ],
            'team' => [
                'id' => $unidade->team->id,
                'name' => $unidade->team->name,
                'owner' => [
                    'id' => $unidade->team->owner->id,
                    'name' => $unidade->team->owner->name,
                    'email' => $unidade->team->owner->email,
                ]
            ],
            'acessibilidade' => $acessibilidade,
            'informacoes' => $informacoes,
            'avaliacao' => $avaliacao,
            'avaliacoes' => $unidade->avaliacoes->map(function ($avaliacao) {
                return [
                    'id' => $avaliacao->id,
                    'status' => $avaliacao->status,
                    'nota_geral' => $avaliacao->nota_geral,
                    'nota_estrutura' => $avaliacao->nota_estrutura,
                    'nota_acessibilidade' => $avaliacao->nota_acessibilidade,
                    'nota_conservacao' => $avaliacao->nota_conservacao,
                    'observacoes' => $avaliacao->observacoes,
                    'avaliador' => [
                        'id' => $avaliacao->avaliador->id,
                        'name' => $avaliacao->avaliador->name,
                    ],
                    'created_at' => $avaliacao->created_at->format('d/m/Y'),
                ];
            }),
        ]);
    }
}