<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\Unidade;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Contracts\CreatesTeams;
use Laravel\Jetstream\Events\AddingTeam;
use Laravel\Jetstream\Jetstream;

class CreateTeam implements CreatesTeams
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  array<string, string>  $input
     */
    public function create(User $user, array $input): Team
    {
        Gate::forUser($user)->authorize('create', Jetstream::newTeamModel());

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:50'],
            'tipo_estrutural' => ['nullable', 'string', 'max:100'],
            'cep' => ['nullable', 'string', 'max:10'],
            'rua' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'complemento' => ['nullable', 'string', 'max:255'],
        ])->validateWithBag('createTeam');

        AddingTeam::dispatch($user);

        $user->switchTeam($team = $user->ownedTeams()->create([
            'name' => $input['name'],
            'personal_team' => true,
        ]));

        // Criar o registro correspondente na tabela de unidades
        Unidade::create([
            'team_id' => $team->id,
            'nome' => $input['name'],
            'codigo' => $input['codigo'] ?? null,
            'tipo_estrutural' => $input['tipo_estrutural'] ?? null,
            'cep' => $input['cep'] ?? null,
            'rua' => $input['rua'] ?? null,
            'numero' => $input['numero'] ?? null, 
            'bairro' => $input['bairro'] ?? null,
            'complemento' => $input['complemento'] ?? null,
            'status' => 'pendente_avaliacao',
        ]);

        return $team;
    }
}