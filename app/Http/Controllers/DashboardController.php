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

        // Verificar se já existe uma unidade cadastrada (apenas se is_draft for false)
        $unidadeCadastrada = false;
        $unidadeStatus = null;
        $rejectionReason = null;
        $isDraft = false;

        if ($user && $user->currentTeam) {
            $unidade = Unidade::where('team_id', $user->currentTeam->id)->first();
            if ($unidade) {
                $unidadeCadastrada = !$unidade->is_draft;
                $unidadeStatus = $unidade->status;
                $rejectionReason = $unidade->rejection_reason;
                $isDraft = $unidade->is_draft; // Passando o valor de is_draft
            }
        }

        // Dados para Super Administrador
        if ($isSuperAdmin) {
            $unidadesCount = Unidade::count();
            $unidadesPendentes = Unidade::where('status', 'pendente_avaliacao')->count();

            return Inertia::render('Dashboard', [
                'isSuperAdmin' => true,
                'isAdmin' => false,
                'isServidor' => false,
                'unidadesCount' => $unidadesCount,
                'unidadesPendentes' => $unidadesPendentes,
                'unidadeCadastrada' => $unidadeCadastrada,
                'unidadeStatus' => $unidadeStatus,
                'rejectionReason' => $rejectionReason,
                'isDraft' => $isDraft,
            ]);
        }
        // Dados para Administrador
        elseif ($isAdmin) {
            return Inertia::render('Dashboard', [
                'isSuperAdmin' => false,
                'isAdmin' => true,
                'isServidor' => false,
                'unidadeCadastrada' => $unidadeCadastrada,
                'unidadeStatus' => $unidadeStatus,
                'rejectionReason' => $rejectionReason,
                'isDraft' => $isDraft,
            ]);
        }
        // Dados para Servidor
        else {
            return Inertia::render('Dashboard', [
                'isSuperAdmin' => false,
                'isAdmin' => false,
                'isServidor' => true,
                'unidadeCadastrada' => $unidadeCadastrada,
                'unidadeStatus' => $unidadeStatus,
                'rejectionReason' => $rejectionReason,
                'isDraft' => $isDraft,
            ]);
        }
    }
}