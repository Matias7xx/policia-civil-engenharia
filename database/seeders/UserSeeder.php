<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Iniciar transação para garantir consistência
        DB::transaction(function () {
            // Criar usuário superadmin
            $superadmin = User::firstOrCreate(
                ['email' => 'superadmin@example.com'],
                [
                    'name' => 'Super Administrador',
                    'password' => Hash::make('password'),
                ]
            );

            // Criar time pessoal para o superadmin
            $superadminTeam = Team::firstOrCreate(
                ['name' => 'Engenharia', 'user_id' => $superadmin->id],
                [
                    'user_id' => $superadmin->id,
                    'personal_team' => true,
                ]
            );

            // Associar superadmin ao time pessoal com papel 'superadmin'
            $superadmin->ownedTeams()->save($superadminTeam);
            $superadmin->current_team_id = $superadminTeam->id;
            $superadmin->teams()->sync([$superadminTeam->id => ['role' => 'superadmin']]);
            $superadmin->save();

            // Criar times compartilhados, com superadmin como owner
            $teams = [
                ['name' => 'RH', 'personal_team' => false],
                ['name' => 'Financeiro', 'personal_team' => false],
            ];

            $teamIds = [];
            foreach ($teams as $teamData) {
                $team = Team::firstOrCreate(
                    ['name' => $teamData['name'], 'user_id' => $superadmin->id],
                    [
                        'user_id' => $superadmin->id,
                        'personal_team' => $teamData['personal_team'],
                    ]
                );
                $teamIds[$teamData['name']] = $team->id;
            }

            // Definir usuários para os times RH e Financeiro, todos com role 'admin'
            $rhUsers =
                [
                    ['name' => 'RH Admin 1', 'email' => 'rh_admin1@example.com', 'team' => 'RH'],
                    ['name' => 'RH Admin 2', 'email' => 'rh_admin2@example.com', 'team' => 'RH'],
                    ['name' => 'RH Admin 3', 'email' => 'rh_admin3@example.com', 'team' => 'RH'],
                    ['name' => 'Financeiro Admin 1', 'email' => 'fin_admin1@example.com', 'team' => 'Financeiro'],
                    ['name' => 'Financeiro Admin 2', 'email' => 'fin_admin2@example.com', 'team' => 'Financeiro'],
                    ['name' => 'Financeiro Admin 3', 'email' => 'fin_admin3@example.com', 'team' => 'Financeiro'],
                ];

            // Criar e associar usuários aos times
            foreach ($rhUsers as $rhUserData) {
                $user = User::firstOrCreate(
                    ['email' => $rhUserData['email']],
                    [
                        'name' => $rhUserData['name'],
                        'password' => 'password',
                    ]
                );

                // Associar usuário ao time especificado com role 'admin'
                $teamId = $teamIds[$rhUserData['team']];
                $user->teams()->sync([$teamId => ['role' => 'admin']]);
                
                // Definir o time como o atual do usuário
                $user->current_team_id = $teamId;
                $user->save();
            }
        });
    }
}