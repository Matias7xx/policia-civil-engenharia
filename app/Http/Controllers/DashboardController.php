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
        $isSuperAdmin = RoleHelper::isSuperAdmin($user);
        $isAdmin = RoleHelper::isAdmin($user) && !$isSuperAdmin;
        $isServidor = RoleHelper::isServidor($user);

        //Verificar se já existe uma unidade cadastrada
        $unidadeCadastrada = false;

        if ($user->currentTeam) {
            $unidade = Unidade::where('team_id', $user->currentTeam->id)->first();
            $unidadeCadastrada = !is_null($unidade);
        }
        
        // Verificar qual o tipo de usuário e renderizar a visualização apropriada
        if ($isSuperAdmin) {
            // Contagem de unidades
            $unidadesCount = Unidade::count();
            
            // Contagem de unidades pendentes de avaliação
            $unidadesPendentes = Unidade::where('status', 'pendente_avaliacao')->count();
            
            return Inertia::render('Dashboard', [
                'isSuperAdmin' => true,
                'isAdmin' => false,
                'isServidor' => false,
                'unidadesCount' => $unidadesCount,
                'unidadesPendentes' => $unidadesPendentes,
                'unidadeCadastrada' => $unidadeCadastrada,
            ]);
        } elseif ($isAdmin) {
            return Inertia::render('Dashboard', [
                'isSuperAdmin' => false,
                'isAdmin' => true,
                'isServidor' => false,
                'unidadeCadastrada' => $unidadeCadastrada,
            ]);
        } else {
            // Usuário servidor - apenas visualização
            return Inertia::render('Dashboard', [
                'isSuperAdmin' => false,
                'isAdmin' => false,
                'isServidor' => true,
                'unidadeCadastrada' => $unidadeCadastrada,
            ]);
        }
    }
}