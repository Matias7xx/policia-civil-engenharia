<?php

namespace App\Http\Controllers;

use App\Models\AvaliacaoUnidade;
use App\Models\Unidade;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class AvaliacaoUnidadeController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Unidade $unidade)
    {
        // Verificar se o usuário é administrador
        $team = Team::findOrFail($unidade->team_id);
        
        if (!Auth::user()->hasTeamRole($team, 'admin')) {
            abort(403, 'Você não tem permissão para avaliar unidades.');
        }
        
        // Validar os dados
        $validatedData = $request->validate([
            'status' => 'required|string|in:aprovada,reprovada,em_revisao',
            'nota_geral' => 'required|numeric|min:0|max:10',
            'nota_estrutura' => 'required|numeric|min:0|max:10',
            'nota_acessibilidade' => 'required|numeric|min:0|max:10',
            'nota_conservacao' => 'required|numeric|min:0|max:10',
            'observacoes' => 'nullable|string',
        ]);
        
        // Adicionar unidade_id e avaliador_id
        $validatedData['unidade_id'] = $unidade->id;
        $validatedData['avaliador_id'] = Auth::id();
        
        // Criar avaliação
        $avaliacao = AvaliacaoUnidade::create($validatedData);
        
        // Atualizar o status da unidade
        $unidade->update(['status' => $validatedData['status']]);
        
        return redirect()->back()->with('flash.banner', 'Avaliação realizada com sucesso.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AvaliacaoUnidade $avaliacao)
    {
        // Verificar se o usuário é administrador
        $unidade = Unidade::findOrFail($avaliacao->unidade_id);
        $team = Team::findOrFail($unidade->team_id);
        
        if (!Auth::user()->hasTeamRole($team, 'admin')) {
            abort(403, 'Você não tem permissão para atualizar avaliações de unidades.');
        }
        
        // Validar os dados
        $validatedData = $request->validate([
            'status' => 'required|string|in:aprovada,reprovada,em_revisao',
            'nota_geral' => 'required|numeric|min:0|max:10',
            'nota_estrutura' => 'required|numeric|min:0|max:10',
            'nota_acessibilidade' => 'required|numeric|min:0|max:10',
            'nota_conservacao' => 'required|numeric|min:0|max:10',
            'observacoes' => 'nullable|string',
        ]);
        
        // Atualizar avaliação
        $avaliacao->update($validatedData);
        
        // Atualizar o status da unidade
        $unidade->update(['status' => $validatedData['status']]);
        
        return redirect()->back()->with('flash.banner', 'Avaliação atualizada com sucesso.');
    }
}