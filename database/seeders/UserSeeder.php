<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Jetstream;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar usuário admin
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // Criar time pessoal para o admin
        $adminTeam = Team::forceCreate([
            'user_id' => $admin->id,
            'name' => 'Engenharia',
            'personal_team' => true,
        ]);

        // Associar admin ao time
        $admin->ownedTeams()->save($adminTeam);
        $admin->current_team_id = $adminTeam->id;
        $admin->save();

        // Criar usuário servidor
        $servidor = User::create([
            'name' => 'Servidor',
            'email' => 'servidor@example.com',
            'password' => Hash::make('password'),
        ]);

        // Criar time pessoal para o servidor
        $servidorTeam = Team::forceCreate([
            'user_id' => $servidor->id,
            'name' => 'Delegacia Tal',
            'personal_team' => true,
        ]);

        // Associar servidor ao time
        $servidor->ownedTeams()->save($servidorTeam);
        $servidor->current_team_id = $servidorTeam->id;
        $servidor->save();

        // Definir explicitamente a role para o servidor
        $servidor->teams()->updateExistingPivot($servidorTeam->id, ['role' => 'servidor']);

        // Definir role "admin" para o admin
        $admin->teams()->updateExistingPivot($adminTeam->id, ['role' => 'admin']);

            DB::table('team_user')
        ->where('team_id', $adminTeam->id)
        ->where('user_id', $admin->id)
        ->update(['role' => 'admin']);
    }
}