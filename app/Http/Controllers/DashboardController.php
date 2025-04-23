<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard screen.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = RoleHelper::isAdmin($user);
        
        // Verificar se o usuário é um administrador
        if ($isAdmin) {
            // Contagem de unidades
            $unidadesCount = Unidade::count();
            
            // Contagem de unidades pendentes de avaliação
            $unidadesPendentes = Unidade::where('status', 'pendente_avaliacao')->count();
            
            return Inertia::render('Dashboard', [
                'isAdmin' => true,
                'unidadesCount' => $unidadesCount,
                'unidadesPendentes' => $unidadesPendentes,
            ]);
        }
        
        // Para servidores, verificar se já existe uma unidade cadastrada para o current_team
        $unidadeCadastrada = false;
        
        if ($user->currentTeam) {
            $unidade = Unidade::where('team_id', $user->currentTeam->id)->first();
            $unidadeCadastrada = !is_null($unidade);
        }
        
        return Inertia::render('Dashboard', [
            'isAdmin' => false,
            'unidadeCadastrada' => $unidadeCadastrada,
        ]);
    }
}