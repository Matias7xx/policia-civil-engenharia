<?php

namespace App\Http\Controllers;

use App\Models\AcessibilidadeUnidade;
use App\Models\Team;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AcessibilidadeUnidadeController extends Controller
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
            'rampa_acesso' => 'nullable|boolean',
            'corrimao' => 'nullable|boolean',
            'piso_tatil' => 'nullable|boolean',
            'banheiro_adaptado' => 'nullable|boolean',
            'elevador' => 'nullable|boolean',
            'sinalizacao_braile' => 'nullable|boolean',
            'observacoes' => 'nullable|string',
        ]);
        
        // Garantir que a unidade_id está correta
        $validatedData['unidade_id'] = $unidade->id;
        
        // Criar o registro de acessibilidade
        $acessibilidade = AcessibilidadeUnidade::create($validatedData);
        
        // Atualizar o status da unidade para pendente de avaliação
        $unidade->update(['status' => 'pendente_avaliacao']);
        
        return redirect()->back()->with('flash.banner', 'Informações de acessibilidade foram salvas com sucesso.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AcessibilidadeUnidade $acessibilidade)
    {
        // Buscar a unidade e o time
        $unidade = Unidade::findOrFail($acessibilidade->unidade_id);
        $team = Team::findOrFail($unidade->team_id);
        
        // Verificar permissão de atualização
        Gate::authorize('update', $team);
        
        // Validar os dados
        $validatedData = $request->validate([
            'rampa_acesso' => 'nullable|boolean',
            'corrimao' => 'nullable|boolean',
            'piso_tatil' => 'nullable|boolean',
            'banheiro_adaptado' => 'nullable|boolean',
            'elevador' => 'nullable|boolean',
            'sinalizacao_braile' => 'nullable|boolean',
            'observacoes' => 'nullable|string',
        ]);
        
        // Atualizar o registro de acessibilidade
        $acessibilidade->update($validatedData);
        
        // Atualizar o status da unidade para pendente de avaliação
        if (!auth()->user()->hasTeamRole($team, 'admin')) {
            $unidade->update(['status' => 'pendente_avaliacao']);
        }
        
        return redirect()->back()->with('flash.banner', 'Informações de acessibilidade foram atualizadas com sucesso.');
    }
}