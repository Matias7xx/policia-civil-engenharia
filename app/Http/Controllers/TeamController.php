<?php

namespace App\Http\Controllers;

use App\Models\AcessibilidadeUnidade;
use App\Models\InformacoesUnidade;
use App\Models\Team;
use App\Models\Unidade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Teams/Index', [
            'teams' => $request->user()->allTeams()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Teams/Create');
    }

    /**
     * Show the team management screen for the given team.
     */
    public function show(Request $request, Team $team)
    {
        Gate::authorize('view', $team);

        // Obter os detalhes da unidade
        $unidade = Unidade::where('team_id', $team->id)->first();
        
        // Obter informações de acessibilidade
        $acessibilidade = null;
        if ($unidade) {
            $acessibilidade = AcessibilidadeUnidade::where('unidade_id', $unidade->id)->first();
        }
        
        // Obter informações estruturais
        $informacoes = null;
        if ($unidade) {
            $informacoes = InformacoesUnidade::where('unidade_id', $unidade->id)->first();
        }

        // Se for administrador, buscar também as avaliações
        $avaliacoes = [];
        if ($request->user()->hasTeamRole($team, 'admin')) {
            // Aqui você pode buscar as avaliações, se necessário
        }

        return Inertia::render('Teams/Show', [
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'personal_team' => $team->personal_team,
                'owner' => $team->owner,
                'users' => $team->users,
                'team_invitations' => $team->teamInvitations,
            ],
            'availableRoles' => array_values(Jetstream::$roles),
            'permissions' => [
                'canAddTeamMembers' => Gate::allows('addTeamMember', $team),
                'canDeleteTeam' => Gate::allows('delete', $team),
                'canRemoveTeamMembers' => Gate::allows('removeTeamMember', $team),
                'canUpdateTeam' => Gate::allows('update', $team),
                'canUpdateTeamMembers' => Gate::allows('updateTeamMember', $team),
            ],
            'unidade' => $unidade,
            'acessibilidade' => $acessibilidade,
            'informacoes' => $informacoes,
            'avaliacoes' => $avaliacoes,
        ]);
    }
}