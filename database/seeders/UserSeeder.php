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
        // Criar usuário superadmin
        $superadmin = User::create([
            'name' => 'Super Administrador',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);

        // Criar time pessoal para o superadmin
        $superadminTeam = Team::forceCreate([
            'user_id' => $superadmin->id,
            'name' => 'Engenharia',
            'personal_team' => true,
        ]);

        // Associar superadmin ao time
        $superadmin->ownedTeams()->save($superadminTeam);
        $superadmin->current_team_id = $superadminTeam->id;
        $superadmin->save();

        // Definir role "superadmin" para o super administrador
        DB::table('team_user')
            ->where('team_id', $superadminTeam->id)
            ->where('user_id', $superadmin->id)
            ->update(['role' => 'superadmin']);
        
        // Criar usuário admin
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // Criar time pessoal para o admin
        $adminTeam = Team::forceCreate([
            'user_id' => $admin->id,
            'name' => 'Delegacia Central',
            'personal_team' => true,
        ]);

        // Associar admin ao time
        $admin->ownedTeams()->save($adminTeam);
        $admin->current_team_id = $adminTeam->id;
        $admin->save();

        // Definir role "admin" para o admin
        DB::table('team_user')
            ->where('team_id', $adminTeam->id)
            ->where('user_id', $admin->id)
            ->update(['role' => 'admin']);

        // Criar usuário servidor
        $servidor = User::create([
            'name' => 'Servidor',
            'email' => 'servidor@example.com',
            'password' => Hash::make('password'),
        ]);

        // Criar time pessoal para o servidor
        $servidorTeam = Team::forceCreate([
            'user_id' => $servidor->id,
            'name' => 'Delegacia Regional',
            'personal_team' => true,
        ]);

        // Associar servidor ao time
        $servidor->ownedTeams()->save($servidorTeam);
        $servidor->current_team_id = $servidorTeam->id;
        $servidor->save();

        // Definir role "servidor" para o servidor
        DB::table('team_user')
            ->where('team_id', $servidorTeam->id)
            ->where('user_id', $servidor->id)
            ->update(['role' => 'servidor']);

            $superadmin->teams()->sync([$superadminTeam->id => ['role' => 'superadmin']]);
            $admin->teams()->sync([$adminTeam->id => ['role' => 'admin']]);
            $servidor->teams()->sync([$servidorTeam->id => ['role' => 'servidor']]);
    }
}