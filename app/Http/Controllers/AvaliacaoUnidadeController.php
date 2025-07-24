<?php

namespace App\Http\Controllers;

use App\Models\AvaliacaoUnidade;
use App\Models\Unidade;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvaliacaoUnidadeController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Unidade $unidade)
    {
        // Verificar se o usuário é superadmin
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Você não tem permissão para avaliar unidades.');
        }
        
        // Verificar se a unidade está aprovada
        if ($unidade->status !== 'aprovada') {
            return redirect()->back()->with('flash.banner', 'Somente unidades com status "Aprovada" podem ser avaliadas.')
                            ->with('flash.bannerStyle', 'danger');
        }
        
        // Validar os dados
        $validatedData = $request->validate([
            'nota_geral' => 'required|numeric|min:1|max:10',
            'nota_estrutura' => 'required|numeric|min:1|max:10',
            'nota_acessibilidade' => 'required|numeric|min:1|max:10',
            'observacoes' => 'nullable|string',
        ]);
        
        // Adicionar unidade_id e avaliador_id
        $avaliacaoData = [
            'unidade_id' => $unidade->id,
            'avaliador_id' => Auth::id(),
            'status' => 'aprovada', // Definindo explicitamente como aprovada
            'nota_geral' => $validatedData['nota_geral'],
            'nota_estrutura' => $validatedData['nota_estrutura'],
            'nota_acessibilidade' => $validatedData['nota_acessibilidade'],
            'observacoes' => $validatedData['observacoes'],
        ];
        
        // Criar avaliação
        $avaliacao = AvaliacaoUnidade::create($avaliacaoData);
        
        return redirect()->back()->with('success', 'Avaliação realizada com sucesso.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AvaliacaoUnidade $avaliacao)
    {
        // Verificar se o usuário é superadmin
        if (!Auth::user()->isSuperAdmin) {
            abort(403, 'Você não tem permissão para atualizar avaliações de unidades.');
        }
        
        // Validar os dados
        $validatedData = $request->validate([
            'nota_geral' => 'required|numeric|min:1|max:10',
            'nota_estrutura' => 'required|numeric|min:1|max:10',
            'nota_acessibilidade' => 'required|numeric|min:1|max:10',
            'observacoes' => 'nullable|string',
        ]);
        
        // Atualizar avaliação
        $avaliacao->update([
            'nota_geral' => $validatedData['nota_geral'],
            'nota_estrutura' => $validatedData['nota_estrutura'],
            'nota_acessibilidade' => $validatedData['nota_acessibilidade'],
            'observacoes' => $validatedData['observacoes'],
        ]);
        
        return redirect()->back()->with('success', 'Avaliação atualizada com sucesso.');
    }
}