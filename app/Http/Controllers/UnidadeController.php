<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UnidadeController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $team = $request->user()->currentTeam;
        
        // Verificar permissão de atualização
        Gate::authorize('update', $team);
        
        // Validar os dados
        $validatedData = $request->validate([
            'team_id' => 'required|exists:teams,id',
            'nome' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'tipo_estrutural' => 'nullable|string|max:100',
            'srpc' => 'nullable|string|max:255',
            'dspc' => 'nullable|string|max:255',
            'nivel' => 'nullable|string|max:100',
            'sede' => 'nullable|boolean',
            'cidade_id' => 'nullable|integer',
            'cep' => 'nullable|string|max:10',
            'rua' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'bairro' => 'nullable|string|max:100',
            'complemento' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefone_1' => 'nullable|string|max:20',
            'telefone_2' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'tipo_judicial' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'observacoes' => 'nullable|string',
            'numero_medidor_agua' => 'nullable|string|max:50',
            'numero_medidor_energia' => 'nullable|string|max:50',
        ]);
        
        // Se estiver criando uma nova unidade, definir o status como pendente de avaliação
        $validatedData['status'] = 'pendente_avaliacao';
        $validatedData['team_id'] = $team->id;
        
        // Criar a unidade
        $unidade = Unidade::create($validatedData);
        
        return redirect()->back()->with('flash.banner', 'Informações da unidade foram salvas com sucesso.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unidade $unidade)
    {
        $team = Team::findOrFail($unidade->team_id);
        
        // Verificar permissão de atualização
        Gate::authorize('update', $team);
        
        // Validar os dados
        $validatedData = $request->validate([
            'nome' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'tipo_estrutural' => 'nullable|string|max:100',
            'srpc' => 'nullable|string|max:255',
            'dspc' => 'nullable|string|max:255',
            'nivel' => 'nullable|string|max:100',
            'sede' => 'nullable|boolean',
            'cidade_id' => 'nullable|integer',
            'cep' => 'nullable|string|max:10',
            'rua' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'bairro' => 'nullable|string|max:100',
            'complemento' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefone_1' => 'nullable|string|max:20',
            'telefone_2' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'tipo_judicial' => 'nullable|string|max:100',
            'observacoes' => 'nullable|string',
            'numero_medidor_agua' => 'nullable|string|max:50',
            'numero_medidor_energia' => 'nullable|string|max:50',
        ]);

        // Se não for admin, não permitir a mudança do status
        if (!auth()->user()->hasTeamRole($team, 'admin')) {
            unset($validatedData['status']);
            
            // Após edição por um servidor, retornar o status para pendente de avaliação
            $validatedData['status'] = 'pendente_avaliacao';
        }
        
        // Atualizar a unidade
        $unidade->update($validatedData);
        
        return redirect()->back()->with('flash.banner', 'Informações da unidade foram atualizadas com sucesso.');
    }
}